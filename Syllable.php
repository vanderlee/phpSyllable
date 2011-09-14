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
	 * @version 0.0.1
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
	interface ISyllableCacheStrategy {
		public function __set($key, $value);
		public function __get($key);
		public function __isset($key);
		public function __unset($key);
	}
	
    /**
     * Cache strategy that uses serialized arrays written to flat text files.
     */    
	class SerializedSyllableCache implements ISyllableCacheStrategy {
		protected $language = null;
		protected $path     = null;
        
		public function __construct($language, $path) {
			$this->language = $language;
			$this->path     = $path;
		}

		private function _filename($key) {
			return $this->path.'/syllable.'.$this->language.'.'.$key.'.ser';;
		}
		
		public function __set($key, $value) {
			$file = $this->_filename($key);
			file_put_contents($file, serialize($value));
			chmod($file, 0777);
		}
		public function __get($key) {
			return unserialize(file_get_contents($this->_filename($key)));			
		}
		public function __isset($key) {
			return file_exists($this->_filename($key));
		}
		
		public function __unset($key) {
			unlink($this->_filename($key));
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
		protected $hyphenation      = null;		
			
		protected $language			= null;
		protected $hyphen			= null;
		protected $treshold			= null;
		protected $min_word_length	= 2;
		
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
				
				$cache = new SerializedSyllableCache($language, dirname(__FILE__).'/cache');
				
				if ($cache !== null && isset($cache->patterns) && isset($cache->hyphenation)) {
					$this->patterns		= $cache->patterns;
					$this->hyphenation	= $cache->hyphenation;								
				} else {
					$this->patterns		= array();
					$this->hyphenation	= array();				
					
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
											$this->patterns[preg_replace('~\d~', '', $m[0])] = $m[0];
											$offset += strlen($m[0]);
										}
										continue;	// next token
									break;

									case 'hyphenation':
										if (preg_match('~^\pL\pM*(-|\pL\pM*)+\pL\pM*~u', substr($line, $offset), $m) === 1) {
											$this->hyphenation[preg_replace('~\-~', '', $m[0])] = $m[0];
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
						$cache->patterns	= $this->patterns;
						$cache->hyphenation	= $this->hyphenation;
					}
				}
			}
		}
		
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

		public function __construct($language = 'en', $treshold = self::TRESHOLD_AVERAGE, $hyphen = null) {
			$this->setLanguage($language);
			$this->setTreshold($treshold);
			$this->setHyphen($hyphen? $hyphen : new SoftHyphen());
		}
			
        /**
         * Splits a word into an array of syllables.
         * @param string $word the word to be split.
         * @return array array of syllables.
         */
		public function splitWord($word) {
			// Is this word smaller than the miminal length requirement?
			if (strlen($word) < $this->min_word_length) {
				return $word;
			}
		
			// Is it a pre-hyphenated word?
			if (array_key_exists($word, $this->hyphenation)) {
				return str_replace('-', $this->hyphen, $this->hyphenation[$word]);
			}
			
			// Convenience array
			$chars = str_split(".$word.");
			
			// Maximize
			$before = array();
			for ($start = 0; $start < count($chars); ++$start) {
				for ($length = $this->min_word_length; $length <= count($chars) - $start; ++$length) {
					$subword = join(array_slice($chars, $start, $length));
					if (array_key_exists($subword, $this->patterns)) {
						preg_match_all('~(\d?)(\D?)~', $this->patterns[$subword], $matches, PREG_PATTERN_ORDER);
						foreach ($matches[1] as $offset => $score) {
							if ( array_key_exists( ($start + $offset), $before ) ) {
								$before[$start + $offset] = max($before[$start + $offset], $score);
							} else {
								$before[$start + $offset] = $score;
							}
						}					
					}
				}
			}

			// Output
			$parts = array();
			$part = '';
			for ($i = 1; $i < count($chars) - 1; ++$i) {
				$char	= $chars[$i];
				if ($i > 1 && array_key_exists( $i, $before ) ) {
					$score	= (int)$before[$i];
					if (($score % 2 == 1)				// only odd scores
					 && ($score >= $this->treshold)) {	// only above treshold
						$parts[] = $part;
						$part = '';
					}
				}
				$part .= $char;
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
					$this->_hyphenateHTMLDOMNodes($child, $entity_reference);
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
