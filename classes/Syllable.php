<?php

    /**
     * Main class
     */
	class Syllable {
		/**
		 * @deprecated since version 1.2
		 */
		const TRESHOLD_LEAST        = PHP_INT_MAX;
		/**
		 * @deprecated since version 1.2
		 */
		const TRESHOLD_AVERAGE      = PHP_INT_MAX;
		/**
		 * @deprecated since version 1.2
		 */
		const TRESHOLD_MOST         = PHP_INT_MAX;

		/**
		 * @var Syllable_Cache_Interface
		 */
		private	$Cache;

		/**
		 * @var Syllable_Cache_Interface
		 */
		private	$Source;

		/**
		 * @var Syllable_Hyphen_Interface
		 */
		private $Hyphen;

		private $language;
		
		private $left_min_hyphen	= 2;
		private $right_min_hyphen	= 2;
		private $patterns			= null;
		private $max_pattern		= null;
		private $hyphenation		= null;
		
		private static $cache_dir		= null;
		private static $language_dir	= null;

		public function __construct($language = 'en', $hyphen = null) {
			if (!self::$cache_dir) {
				self::$cache_dir = __DIR__.'/cache';
			}
			$this->setCache(new Syllable_Cache_Json(self::$cache_dir));				
			
			if (!self::$language_dir) {
				self::$language_dir = __DIR__.'/languages';
			}
						
			$this->setLanguage($language);
			
			if ($hyphen === self::TRESHOLD_MOST) {			
				$hyphen = func_get_arg(2);
			}
			
			$this->setHyphen($hyphen? $hyphen : new Syllable_Hyphen_Soft());
		}

		public static function setCacheDir($dir) {
			self::$cache_dir = $dir;
		}
		
		public static function setLanguageDir($dir) {
			self::$language_dir = $dir;
		}
		
		public function setLanguage($language) {
			$this->language = $language;		
			$this->setSource(new Syllable_Source_File($language, self::$language_dir));
		}

		/**
		 * Set the hyphen to use when hyphenating text
		 * @param Mixed $hyphen either a Syllable_Hyphen_Interface or a string, which is turned into a Syllable_Hyphen_Text
		 */
		public function setHyphen($hyphen) {
			$this->Hyphen	= ($hyphen instanceof Syllable_Hyphen_Interface)
							? $hyphen
							: new Syllable_Hyphen_Text($hyphen);
		}

		/**
		 *
		 * @return Syllable_Hyphen_Interface hyphen
		 */
		public function getHyphen() {
			return $this->Hyphen;
		}

		/**
		 * Set the treshold.
		 * This feature is deprecated as it was based on misinterpretation of
		 * the algorithm.
		 * @param type $treshold
		 * @deprecated since version 1.2
		 */
		public function setTreshold($treshold = self::TRESHOLD_MOST) {
			trigger_error('Treshold removed', E_USER_DEPRECATED);
		}

		/**
		 * Get the treshold.
		 * This feature is deprecated as it was based on misinterpretation of
		 * the algorithm.
		 * @return int
		 * @deprecated since version 1.2
		 */
		public function getTreshold() {
			trigger_error('Treshold removed', E_USER_DEPRECATED);
			return self::TRESHOLD_MOST;
		}

		/**
		 *
		 * @param Syllable_Cache_Interface $Cache
		 */
		public function setCache(Syllable_Cache_Interface $Cache = null) {
			$this->Cache = $Cache;
		}

		/**
		 * @return Syllable_Cache_Interface
		 */
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
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?

			$this->loadLanguage();
			
			return $this->parseWord($word);
		}

		public function splitText($text) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?

			$this->loadLanguage();

			$splits = mb_split('[^[:alpha:]]+', $text);
			$parts = array();
			$part = '';
			$pos = 0;

			foreach ($splits as $split) {
				if (mb_strlen($split)) {
					$p = mb_stripos($text, $split, $pos);

					$length = $p - $pos;
					if ($length >= 1) {
						$part .= mb_substr($text, $pos, $length);
					}
					if (!empty($split)) {
						$sw = $this->parseWord($split);
						$index = 0;
						$part .= $sw[$index++];
						$sw_count = count($sw);
						if ($sw_count > 1) {
							do {
								$parts[] = $part;
								$part = $sw[$index++];
							} while ($index < $sw_count);
						}
					}
					$pos = $p + mb_strlen($split);
				}
			}
			$parts[] = $part . mb_substr($text, $pos);

			return $parts;
		}

		public function hyphenateWord($word) {
			$parts = $this->splitWord($word);
			return $this->Hyphen->joinText($parts);
		}

		public function hyphenateText($text) {
			$parts = $this->splitText($text);
			return $this->Hyphen->joinText($parts);
		}

		public function hyphenateHtml($html) {
			$dom = new DOMDocument();
			$dom->resolveExternals = true;
			$dom->loadHTML($html);

			$this->hyphenateHtmlDom($dom);

			return $dom->saveHTML();
		}

		private function hyphenateHtmlDom(DOMNode $node) {
			if ($node->hasChildNodes()) {
				foreach ($node->childNodes as $child) {
					$this->hyphenateHtmlDom($child);
				}
			}
			if ($node instanceof DOMText) {
				$parts = $this->splitText($node->data);

				$this->Hyphen->joinHtmlDom($parts, $node);
			}
		}
		
		public function histogramText($text) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?
			
			$this->loadLanguage();			
			
			$counts = array();
			foreach (mb_split('[^[:alpha:]]+', $text) as $split) {
				if (mb_strlen($split)) {
					$count = count($this->parseWord($split));
					if (isset($counts[$count])) {
						++$counts[$count];
					} else {
						$counts[$count] = 1;
					}
				}
			}
			
			return $counts;
		}
		
		public function countWordsText($text) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?
			
			$this->loadLanguage();			
			
			$count = 0;
			foreach (mb_split('[^[:alpha:]]+', $text) as $split) {
				if (mb_strlen($split)) {
					++$count;
				}
			}
			
			return $count;
		}
		
		public function countPolysyllablesText($text) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?
			
			$this->loadLanguage();			
			
			$count = 0;
			foreach (mb_split('[^[:alpha:]]+', $text) as $split) {
				if (mb_strlen($split) && count($this->parseWord($split)) >= 3) {
					++$count;
				}
			}
			
			return $count;
		}

		private function loadLanguage() {
			$loaded = false;
			
			$cache = $this->getCache();
			if ($cache !== null) {
				$cache->open($this->language);

				if (isset($cache->patterns)
				 && isset($cache->max_pattern)
				 && isset($cache->hyphenation)
				 && isset($cache->left_min_hyphen)
				 && isset($cache->right_min_hyphen)) {
					$this->patterns			= $cache->patterns;
					$this->max_pattern		= $cache->max_pattern;
					$this->hyphenation		= $cache->hyphenation;
					$this->left_min_hyphen	= $cache->left_min_hyphen;
					$this->right_min_hyphen	= $cache->right_min_hyphen;
					
					$loaded = true;
				 }
			}
			
			if (!$loaded) {
				$this->patterns			= array();
				$this->max_pattern		= 0;
				$this->hyphenation		= array();
				$this->left_min_hyphen	= 2;
				$this->right_min_hyphen	= 2;

				// parser state
				$command = FALSE;
				$braces = FALSE;

				// parse .tex file
				$tex = $this->getSource();
				foreach ($tex as $line) {
					$offset = 0;
					$strlen_line = mb_strlen($line);
					while ($offset < $strlen_line) {
						// %comment
						if ($line{$offset} === '%') {
							break;	// ignore rest of line
						}

						// \command
						if (preg_match('~^\\\\([[:alpha:]]+)~', mb_substr($line, $offset), $m) === 1) {
							$command = $m[1];
							$offset += mb_strlen($m[0]);
							continue;	// next token
						}

						// {
						if ($line{$offset} === '{') {
							$braces = TRUE;
							++$offset;
							continue;	// next token
						}

						// content
						if ($braces) {
							switch ($command) {
								case 'patterns':
									if (preg_match('~^(?:\pL\pM*|\pN|[-.])+~u', mb_substr($line, $offset), $m) === 1) {
										$numbers = '';
										$pattern = '';
										$strlen = 0;
										$expect_number = true;
										foreach (preg_split('/(?<!^)(?!$)/u', $m[0]) as $char) {
											if (is_numeric($char)) {
												$numbers .= $char;
												$expect_number = false;
											} else {
												if ($expect_number) {
													$numbers .= '0';
												}
												$pattern .= $char;
												++$strlen;
												$expect_number = true;
											}
											++$offset;
										}
										if ($expect_number) {
											$numbers .= '0';
										}

										$this->patterns[$pattern]	= $numbers;
										if ($strlen > $this->max_pattern) {
											$this->max_pattern = $strlen;
										}
									}
									continue;	// next token
								break;

								case 'hyphenation':
									if (preg_match('~^\pL\pM*(-|\pL\pM*)+\pL\pM*~u', substr($line, $offset), $m) === 1) {
										$hyphenation = preg_replace('~\-~', '', $m[0]);
										$this->hyphenation[$hyphenation] = $m[0];
										$offset += strlen($m[0]);
									}
									continue;	// next token
								break;
							}
						}

						// }
						if ($line[$offset] === '}') {
							$braces = FALSE;
							$command = FALSE;
							++$offset;
							continue;	// next token
						}

						// ignorable content, skip one char
						++$offset;
					}
					
					// parse hyphen file
					$minHyphens = $tex->getMinHyphens();					
					if ($minHyphens) {
						$this->left_min_hyphen	= $minHyphens[0];
						$this->right_min_hyphen	= $minHyphens[1];
					}

					if ($cache !== null) {
						$cache->patterns			= $this->patterns;
						$cache->max_pattern			= $this->max_pattern;
						$cache->hyphenation			= $this->hyphenation;
						$cache->left_min_hyphen		= $this->left_min_hyphen;
						$cache->right_min_hyphen	= $this->right_min_hyphen;

						$cache->close();
					}					
				}
			}
		}

        /**
         * Splits a word into an array of syllables.
         * @param string $word the word to be split.
         * @return array array of syllables.
         */
		private function parseWord($word) {
			$word_length = mb_strlen($word);

			// Is this word smaller than the miminal length requirement?
			if ($word_length < $this->left_min_hyphen + $this->right_min_hyphen) {
				return array($word);
			}

			// Is it a pre-hyphenated word?
			if (isset($this->hyphenation[$word])) {
				return mb_split('-', $this->hyphenation[$word]);
			}

			// Convenience array
			$text			= '.'.mb_strtolower($word).'.';
			$text_length	= $word_length + 2;
			$pattern_length = $this->max_pattern < $text_length ? $this->max_pattern : $text_length;

			// Maximize
			$before		= array();
			$end		= $text_length - $this->right_min_hyphen;
			for ($start = 0; $start < $end; ++$start) {
				$max_length = $start + $pattern_length;
				if ($text_length - $start < $max_length) {
					$max_length = $text_length - $start;
				}
				for ($length = 1; $length <= $max_length; ++$length) {
					$subword = mb_substr($text, $start, $length);				
					if (isset($this->patterns[$subword])) {
						$scores = $this->patterns[$subword];
						$scores_length = $length + 1;
						for ($offset = 0; $offset < $scores_length; ++$offset) {
							$score = $scores{$offset};
							if (!isset($before[($start + $offset)]) || $score > $before[$start + $offset]) {
								$before[$start + $offset] = $score;
							}
						}
					}
				}
			}

			// Output
			$parts	= array();
			$part	= mb_substr($word, 0, $this->left_min_hyphen);
			for ($i = $this->left_min_hyphen + 1; $i < $end; ++$i) {
				if (isset($before[$i])) {
					$score	= (int)$before[$i];
					if ($score & 1) {	// only odd
						//$part .= $score; // debugging
						$parts[] = $part;	
						$part = '';
					}
				}
				$part .= mb_substr($word, $i - 1, 1);
			}
			for (; $i < $text_length - 1; ++$i) {
				$part .= mb_substr($word, $i - 1, 1);
			}
			if (!empty($part)) {
				$parts[] = $part;
			}

			return $parts;
		}
	}