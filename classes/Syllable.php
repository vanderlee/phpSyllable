<?php

    /**
     * Main class
     */
	class Syllable {
        /**
         * Version string, used to recalculate language caches if needed.
         */
        const CACHE_VERSION         = 1.4;

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

		/**
		 * Create a new Syllable class, with defaults
		 * @param string $language
		 * @param string|Syllable_Hyphen_Interface $hyphen
		 */
		public function __construct($language = 'en', $hyphen = null) {
			if (!self::$cache_dir) {
				self::$cache_dir = __DIR__.'/../cache';
			}
			$this->setCache(new Syllable_Cache_Json(self::$cache_dir));				
			
			if (!self::$language_dir) {
				self::$language_dir = __DIR__.'/../languages';
			}
						
			$this->setLanguage($language);
			
			if ($hyphen === self::TRESHOLD_MOST) {			
				$hyphen = func_get_arg(2);
			}
			
			$this->setHyphen($hyphen? $hyphen : new Syllable_Hyphen_Soft());
		}

		/**
		 * Set the directory where compiled language files may be stored.
		 * Default to the `cache` subdirectory of the current directory.
		 * @param string $dir
		 */
		public static function setCacheDir($dir) {
			self::$cache_dir = $dir;
		}

		/**
		 * Set the directory where language source files can be found.
		 * Default to the `languages` subdirectory of the current directory.
		 * @param string $dir
		 */
		public static function setLanguageDir($dir) {
			self::$language_dir = $dir;
		}

		/**
		 * Set the language whose rules will be used for hyphenation.
		 * @param string $language
		 */
		public function setLanguage($language) {
			$this->language = $language;		
			$this->setSource(new Syllable_Source_File($language, self::$language_dir));
		}

		/**
		 * Set the hyphen text or object to use as a hyphen marker.
		 * @param Mixed $hyphen either a Syllable_Hyphen_Interface or a string, which is turned into a Syllable_Hyphen_Text
		 */
		public function setHyphen($hyphen) {
			$this->Hyphen	= ($hyphen instanceof Syllable_Hyphen_Interface)
							? $hyphen
							: new Syllable_Hyphen_Text($hyphen);
		}

		/**
		 * Get the current hyphen object.
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

        /**
         * @return Syllable_Source_Interface
         */
		public function getSource() {
			return $this->Source;
		}

		/**
		 * Split a single word on where the hyphenation would go.
		 * @param string $text
		 * @return array
		 */
		public function splitWord($word) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?

			$this->loadLanguage();
			
			return $this->parseWord($word);
		}

		/**
		 * Split a text on where the hyphenation would go.
		 * @param string $text
		 * @return array
		 */
		public function splitText($text) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?

			$this->loadLanguage();

			$splits = mb_split('[^\'[:alpha:]]+', $text);
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

		/**
		 * Hyphenate a single word.
		 * @param string $html
		 * @return string
		 */
		public function hyphenateWord($word) {
			$parts = $this->splitWord($word);
			return $this->Hyphen->joinText($parts);
		}

		/**
		 * Hyphenate all words in the plain text.
		 * @param string $html
		 * @return string
		 */
		public function hyphenateText($text) {
			$parts = $this->splitText($text);
			return $this->Hyphen->joinText($parts);
		}

		/**
		 * Hyphenate all readable text in the HTML, excluding HTML tags and
		 * attributes.
		 * @param string $html
		 * @return string
		 */
		public function hyphenateHtml($html) {
			$dom = new DOMDocument();
			$dom->resolveExternals = true;
			$dom->loadHTML($html);

			$this->hyphenateHtmlDom($dom);

			return $dom->saveHTML();
		}

		/**
		 * Add hyphenation to the DOM nodes.
		 * @param DOMNode $node
		 */
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

		/**
		 * Count the number of syllables in the text and return a map with
		 * syllable count as key and number of words for that syllable count as
		 * the value.
		 * @param string $text
		 * @return array
		 */
		public function histogramText($text) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?
			
			$this->loadLanguage();			
			
			$counts = array();
			foreach (mb_split('[^\'[:alpha:]]+', $text) as $split) {
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

		/**
		 * Count the number of words in the text.
		 * @param string $text
		 * @return int
		 */
		public function countWordsText($text) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?
			
			$this->loadLanguage();			
			
			$count = 0;
			foreach (mb_split('[^\'[:alpha:]]+', $text) as $split) {
				if (mb_strlen($split)) {
					++$count;
				}
			}
			
			return $count;
		}

		/**
		 * Count the number of polysyllables in the text.
		 * @param string $text
		 * @return int
		 */
		public function countPolysyllablesText($text) {
			mb_internal_encoding('UTF-8');	//@todo upwards?
			mb_regex_encoding('UTF-8');	//@todo upwards?
			
			$this->loadLanguage();			
			
			$count = 0;
			foreach (mb_split('[^\'[:alpha:]]+', $text) as $split) {
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

				if (isset($cache->version) && $cache->version == self::CACHE_VERSION
				 && isset($cache->patterns)
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
                $source = $this->getSource();
				$this->patterns			= $source->getPatterns();
				$this->max_pattern		= $source->getMaxPattern();
				$this->hyphenation		= $source->getHyphentations();

				$this->left_min_hyphen	= 2;
				$this->right_min_hyphen	= 2;
                $minHyphens = $source->getMinHyphens();
                if ($minHyphens) {
                    $this->left_min_hyphen	= $minHyphens[0];
                    $this->right_min_hyphen	= $minHyphens[1];
                }
                
                if ($cache !== null) {
                    $cache->version             = self::CACHE_VERSION;
                    $cache->patterns			= $this->patterns;
                    $cache->max_pattern			= $this->max_pattern;
                    $cache->hyphenation			= $this->hyphenation;
                    $cache->left_min_hyphen		= $this->left_min_hyphen;
                    $cache->right_min_hyphen	= $this->right_min_hyphen;

                    $cache->close();
                }

                $loaded = true;
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