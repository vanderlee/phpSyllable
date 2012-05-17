<?php
	/**
	 * phpSyllable
	 * Splits up text into syllables and/or hyphenates text according to TeXbook language rules.
	 *
	 * Based on the work by Frank M. Liang (http://www.tug.org/docs/liang/)
	 * and the many volunteers in the TeX community.
	 *
	 * Patterns from the CTAN archive
	 * - http://www.ctan.org/tex-archive/language/hyph-utf8/tex/generic/hyph-utf8/patterns/tex/
	 * - http://www.ctan.org/tex-archive/language/hyphenation/
	 *
	 * @version 0.2
	 * @author Martijn W. van der Lee <martijn-at-vanderlee-dot-com>
	 * @author Wim Muskee <wimmuskee-at-gmail-dot-com>
	 * @copyright Copyright (c) 2011, Martijn W. van der Lee
	 * @license http://www.opensource.org/licenses/mit-license.php
     *
     * @todo Ability to set cache and language strategies.
     * @todo Errors on file-not-found or otherwise in strategies. When to quit?
	 */

	interface IHyphenStrategy {
		public function joinText($parts);
		public function joinHTMLDOM($parts, DOMNode $node);
	}

	class DashHyphen implements IHyphenStrategy {
		public function joinText($parts) {
			return join('-', $parts);
		}

		public function joinHTMLDOM($parts, DOMNode $node) {
			$node->data = $this->joinText($parts);
		}
	}

	abstract class EntityHyphenStrategy implements IHyphenStrategy {
		protected $entity = null;

		public function joinText($parts) {
			return join('&'.$this->entity.';', $parts);
		}

		public function joinHTMLDOM($parts, DOMNode $node) {
			if (($p = count($parts)) > 1) {
				$node->data = $parts[--$p];
				while (--$p >= 0) {
					$node = $node->parentNode->insertBefore($node->ownerDocument->createEntityReference($this->entity), $node);
					$node = $node->parentNode->insertBefore($node->ownerDocument->createTextNode($parts[$p]), $node);
				}
			}
		}
	}

	class SoftHyphen extends EntityHyphenStrategy {
		protected $entity = 'shy';
	}

	class ZeroWidthSpaceHyphen extends EntityHyphenStrategy{
		protected $entity = '#8203';
	}

    /**
     * Defines the Cache strategy interface
     * Create your own caching strategy to store the hyphenation and patterns
     * arrays in the location you want. i.e. from a database or remote server.
     */
	interface ICacheStrategy {
		public function __set($key, $value);
		public function __get($key);
		public function __isset($key);
		public function __unset($key);
	}

	abstract class SingleFileCacheStrategy implements ICacheStrategy {
		private $language	= null;
		private $path		= null;
		private $data		= array();

		abstract protected function _encode($array);
		abstract protected function _decode($array);
		abstract protected function _getFilename($language);

		public function __construct($language, $path) {
			$this->language = $language;
			$this->path     = $path;
		}

		private function _filename() {
			return $this->path.'/'.$this->_getFilename($this->language);
		}

		private function _load() {
			if (empty($this->data)) {
				$file = $this->_filename();
				if (is_file($file)) {
					$this->data = $this->_decode(file_get_contents($file), true);
				}
			}
		}

		private function _save() {
			$file = $this->_filename();
			file_put_contents($file, $this->_encode($this->data));
			chmod($file, 0777);
		}

		public function __set($key, $value) {
			$this->data[$key] = $value;
			$this->_save();
		}

		public function __get($key) {
			$this->_load();
			return $this->data[$key];
		}

		public function __isset($key) {
			$this->_load();
			return isset($this->data[$key]);
		}

		public function __unset($key) {
			unset($this->data[$key]);
		}
	}

	/**
	 * Single-file cache using JSON format to encode data
	 */
	class JSONCache extends SingleFileCacheStrategy {
		protected function _encode($array) {
			return json_encode($array);
		}

		protected function _decode($array) {
			return json_decode($array, true);
		}

		protected function _getFilename($language) {
			return "syllable.{$language}.json";
		}
	}

	/**
	 * Single-file cache using PHP-native serialization to encode data
	 */
	class SerializedCache extends SingleFileCacheStrategy {
		protected function _encode($array) {
			return serialize($array);
		}

		protected function _decode($array) {
			return unserialize($array);
		}

		protected function _getFilename($language) {
			return "syllable.{$language}.serialized";
		}
	}

    /**
     * Defines the interface for Language strategies.
     * Create your own language strategy to load the TeX files from a different
     * source. i.e. filenaming system, database or remote server.
     */
    interface ISyllableSourceStrategy extends Iterator {}

    /**
     * Default language strategy tries to load TeX files from a relative path
     * to the class sourcefile.
     */
    class FileSyllableLanguage implements ISyllableSourceStrategy {
        private $lines      = array();
        private $position   = 0;

        public function __construct($language, $path) {
            $this->lines    = file("$path/hyph-{$language}.tex");
            $this->position = 0;
        }

        function rewind() {
            $this->position = 0;
        }

        function current() {
            return $this->lines[$this->position];
        }

        function key() {
            return $this->position;
        }

        function next() {
            ++$this->position;
        }

        function valid() {
            return isset($this->lines[$this->position]);
        }
    }

    /**
     * Main class
     */
	class Syllable {
		const TRESHOLD_LEAST        = 5;
		const TRESHOLD_AVERAGE      = 3;
		const TRESHOLD_MOST         = 1;

		protected $patterns         = null;
		protected $max_pattern		= null;
		protected $hyphenation      = null;
		protected $min_hyphenation  = null;

		protected $language			= null;
		protected $hyphen			= null;
		protected $treshold			= null;
		protected $min_word_length	= 2;

		public function __construct($language = 'en', $treshold = self::TRESHOLD_AVERAGE, $hyphen = null) {
			$this->setLanguage($language);
			$this->setTreshold($treshold);
			$this->setHyphen($hyphen? $hyphen : new SoftHyphen());
		}

        /**
         * Set the language to use for splitting syllables.
         * Loads from cache if available, otherwise parses TeX hyphen files.
         * Assumes basic syntax for TeX files (simplified):
         *  file    := ( line crlf )*
         *  crlf    := CR | LF | ( CF LF )
         *  line    := comment | ( command "{" content* "}" )
         *  comment := "%" ANY*
         *  command := "\" ALPHA+
         *  content := ANY+             (depends on command)
         * @param type $language
         */
		public function setLanguage($language) {
			if ($language !== null && $language != $this->language) {
				$this->language = $language;

				$cache = new JSONCache($language, dirname(__FILE__).'/cache');

				if ($cache !== null
						&& isset($cache->patterns)
						&& isset($cache->max_pattern)
						&& isset($cache->hyphenation)
						&& isset($cache->min_hyphenation)
						) {
					$this->patterns			= $cache->patterns;
					$this->max_pattern		= $cache->max_pattern;
					$this->hyphenation		= $cache->hyphenation;
					$this->min_hyphenation	= $cache->min_hyphenation;
				} else {
					$this->patterns			= array();
					$this->max_pattern		= 0;
					$this->hyphenation		= array();
					$this->min_hyphenation	= PHP_INT_MAX;

					// parser state
					$command = FALSE;
					$braces = FALSE;

                    $tex = new FileSyllableLanguage($language, dirname(__FILE__).'/languages');
                    foreach ($tex as $line) {
						$offset = 0;
						while ($offset < strlen($line)) {
                            // %comment
							if ($line[$offset] == '%') {
								break;	// ignore rest of line
							}

							// \command
							if (preg_match('~^\\\\([[:alpha:]]+)~', substr($line, $offset), $m) === 1) {
								$command = $m[1];
								$offset += strlen($m[0]);
								continue;	// next token
							}

							// {
							if ($line[$offset] == '{') {
								$braces = TRUE;
								++$offset;
								continue;	// next token
							}

							// content
							if ($braces) {
								switch ($command) {
									case 'patterns':
										if (preg_match('~^(\pL\pM*|\pN|\.)+~u', substr($line, $offset), $m) === 1) {
											$numbers = '';
											preg_match_all('~(?:(\d)\D?)|\D~', $m[0], $matches, PREG_PATTERN_ORDER);
											foreach ($matches[1] as $score) {
												$numbers .= is_numeric($score)? $score : 0;
											}
											$pattern = preg_replace('~\d~', '', $m[0]);
											$this->patterns[$pattern]	= $numbers;
											if (isset($pattern{$this->max_pattern})) {
												$this->max_pattern = strlen($pattern);
											}
											$offset += strlen($m[0]);
										}
										continue;	// next token
									break;

									case 'hyphenation':
										if (preg_match('~^\pL\pM*(-|\pL\pM*)+\pL\pM*~u', substr($line, $offset), $m) === 1) {
											$hyphenation = preg_replace('~\-~', '', $m[0]);
											$this->hyphenation[$hyphenation] = $m[0];
											if (!isset($hyphenation{$this->min_hyphenation})) {
												$this->min_hyphenation = strlen($hyphenation);
											}
											$offset += strlen($m[0]);
										}
										continue;	// next token
									break;
								}
							}

							// }
							if ($line[$offset] == '}') {
								$braces = FALSE;
								$command = FALSE;
								++$offset;
								continue;	// next token
							}

							// ignorable content, skip one char
							++$offset;
						}
					}

					if ($cache !== null) {
						$cache->patterns		= $this->patterns;
						$cache->max_pattern		= $this->max_pattern;
						$cache->hyphenation		= $this->hyphenation;
						$cache->min_hyphenation	= $this->min_hyphenation;
					}
				}
			}
		}

		/**
		 *
		 * @param IHyphenStrategy/String $hyphen Either an IHyphenStrategy or
		 * plain string used to join the syllables of words.
		 */
		public function setHyphen($hyphen) {
			if ($hyphen !== null) {
				$this->hyphen	= $hyphen;
			}
		}

		public function setTreshold($treshold) {
			if ($treshold !== null) {
				$this->treshold	= $treshold;
			}
		}

        /**
         * Splits a word into an array of syllables.
         * @param string $word the word to be split.
         * @return array array of syllables.
         */
		public function splitWord($word) {
			$word_length = strlen($word);
			// Is this word smaller than the miminal length requirement?
			if ($word_length < $this->min_word_length) {
				return $word;
			}

			// Is it a pre-hyphenated word?
			if (isset($word{$this->min_hyphenation - 1}) && isset($this->hyphenation[$word])) {
				return str_replace('-', $this->hyphen, $this->hyphenation[$word]);
			}

			// Convenience array
			$text			= '.'.$word.'.';
			$text_length	= $word_length + 2;
			$pattern_length = min($this->max_pattern, $text_length);

			// Maximize
			$before = array();
			for ($start = 0; $start < $text_length - $this->min_word_length; ++$start) {
				$subword = substr($text, $start, $this->min_word_length - 1);
				for ($index = $start + $this->min_word_length - 1; $index < $pattern_length; ++$index) {
					$subword .= $text[$index];
					if (isset($this->patterns[$subword])) {
						$scores = $this->patterns[$subword];
						$scores_length = strlen($scores);
						for ($offset = 0; $offset < $scores_length; ++$offset) {
							$score = $scores[$offset];
							if (!isset($before[($start + $offset)]) || $score > $before[$start + $offset]) {
								$before[$start + $offset] = $score;
							}
						}
					}
				}
			}

			// Output
			$parts = array();
			$part = $text[1];
			for ($i = 2; $i < $text_length - 1; ++$i) {
				if (isset($before[$i])) {
					$score	= (int)$before[$i];
					if (($score % 2)					// only odd scores
					 && ($score >= $this->treshold)) {	// only above treshold
						$parts[] = $part;
						$part = '';
					}
				}
				$part .= $text[$i];
			}
			if (!empty($part)) {
				$parts[] = $part;
			}

			return $parts;
		}

		public function splitText($text) {
			$splits = preg_split('~[^[:alpha:]]+~', $text, null, PREG_SPLIT_OFFSET_CAPTURE);
			$parts = array();
			$part = '';
			$pos = 0;
			foreach ($splits as $split) {
				$length = $split[1] - $pos;
				if ($length >= 1) {
					$part .= substr($text, $pos, $length);
				}
				if (!empty($split[0])) {
					$sw = $this->splitWord($split[0]);
					$index = 0;
					$part .= $sw[$index++];
					if (count($sw) > 1) {
						do {
							$parts[] = $part;
							$part = $sw[$index++];
						} while ($index < count($sw));
					}
				}
				$pos = $split[1] + strlen($split[0]);
			}
			$parts[] = $part;

			return $parts;
		}

		public function hyphenateWord($word) {
			$parts = $this->splitWord($word);
			if ($this->hyphen instanceof IHyphenStrategy) {
				return $this->hyphen->joinText($parts);
			} else {
				return join($this->hyphen, $parts);
			}
		}

		public function hyphenateText($text) {
			$parts = $this->splitText($text);
			if ($this->hyphen instanceof IHyphenStrategy) {
				return $this->hyphen->joinText($parts);
			} else {
				return join($this->hyphen, $parts);
			}
		}

		public function hyphenateHTML($html) {
			$dom = new DOMDocument();
			$dom->resolveExternals = true;
			$dom->loadHTML($html);

			$this->_hyphenateHTMLDOMNodes($dom);

			return $dom->saveHTML();
		}

		private function _hyphenateHTMLDOMNodes(DOMNode $node) {
			if ($node->hasChildNodes()) {
				foreach ($node->childNodes as $child) {
					$this->_hyphenateHTMLDOMNodes($child);
				}
			}
			if ($node instanceof DOMText) {
				$parts = $this->splitText($node->data);

				if ($this->hyphen instanceof IHyphenStrategy) {
					$this->hyphen->joinHTMLDOM($parts, $node);
				} else {
					$node->data = join($this->hyphen, $parts);
				}
			}
		}
	}
?>
