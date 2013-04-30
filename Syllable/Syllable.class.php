<?php

    /**
     * Main class
     */
	class Syllable {
		const TRESHOLD_LEAST        = 5;
		const TRESHOLD_AVERAGE      = 3;
		const TRESHOLD_MOST         = 1;

		private	$Cache				= null;
		private	$Source				= null;

		protected $patterns         = null;
		protected $max_pattern		= null;
		protected $hyphenation      = null;
		protected $min_hyphenation  = null;

		protected $language			= null;
		protected $hyphen			= null;
		protected $treshold			= null;
		protected $min_word_length	= 2;

		public function __construct($language = 'en', $treshold = self::TRESHOLD_AVERAGE, $hyphen = null) {
			$this->setCache(new Syllable_Cache_Json($language, dirname(__FILE__).'/cache'));
			$this->setSource(new Syllable_Source_File($language, dirname(__FILE__).'/languages'));
			$this->setTreshold($treshold);
			$this->setHyphen($hyphen? $hyphen : new Syllable_Hyphen_Soft());
		}

		public function setHyphen($hyphen) {
			if ($hyphen !== null) {
				$this->hyphen	= $hyphen;
			}
		}

		public function setTreshold($treshold = self::TRESHOLD_MOST) {
			$this->treshold	= $treshold;
		}

		public function getTreshold() {
			return $this->treshold;
		}

		public function setCache(Syllable_Cache_Interface $Cache) {
			$this->Cache = $Cache;
		}

		public function getCache() {
			return $this->Cache;
		}

		public function setSource(Syllable_Source_Interface $Source) {
			$this->Source = $Source;
		}

		public function getSource() {
			return $this->Source;
		}

		public function splitWord($word) {
			$this->parseTex();
			
			return $this->parseWord($word);
		}

		public function splitText($text) {
			$this->parseTex();
			
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
					$sw = $this->parseWord($split[0]);
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
			if ($this->hyphen instanceof Syllable_Hyphen_Interface) {
				return $this->hyphen->joinText($parts);
			} else {
				return join($this->hyphen, $parts);
			}
		}

		public function hyphenateText($text) {
			$parts = $this->splitText($text);
			if ($this->hyphen instanceof Syllable_Hyphen_Interface) {
				return $this->hyphen->joinText($parts);
			} else {
				return join($this->hyphen, $parts);
			}
		}

		public function hyphenateHtml($html) {
			$dom = new DOMDocument();
			$dom->resolveExternals = true;
			$dom->loadHTML($html);

			$this->hyphenateHtmlDom($dom);

			return $dom->saveHTML();
		}

		private function parseTex() {
			if ($this->patterns && $this->max_pattern && $this->hyphenation && $this->min_hyphenation) {
				return;
			}

			$cache = $this->getCache();
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

				$tex = $this->getSource();
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

        /**
         * Splits a word into an array of syllables.
         * @param string $word the word to be split.
         * @return array array of syllables.
         */
		private function parseWord($word) {
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

		private function hyphenateHtmlDom(DOMNode $node) {
			if ($node->hasChildNodes()) {
				foreach ($node->childNodes as $child) {
					$this->hyphenateHtmlDom($child);
				}
			}
			if ($node instanceof DOMText) {
				$parts = $this->splitText($node->data);

				if ($this->hyphen instanceof Syllable_Hyphen_Interface) {
					$this->hyphen->joinHtmlDom($parts, $node);
				} else {
					$node->data = join($this->hyphen, $parts);
				}
			}
		}
	}