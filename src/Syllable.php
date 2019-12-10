<?php

namespace Vanderlee\Syllable;

use Vanderlee\Syllable\Cache\Cache;
use Vanderlee\Syllable\Cache\Json;
use Vanderlee\Syllable\Hyphen\Hyphen;
use Vanderlee\Syllable\Hyphen\Soft;
use Vanderlee\Syllable\Hyphen\Text;
use Vanderlee\Syllable\Source\File;
use Vanderlee\Syllable\Source\Source;

/**
 * Main class
 */
class Syllable
{

    /**
     * Version string, used to recalculate language caches if needed.
     */
    const CACHE_VERSION = 1.4;

    /**
     * @var Cache
     */
    private $Cache;

    /**
     * @var Source
     */
    private $Source;

    /**
     * @var Hyphen
     */
    private $Hyphen;

    /**
     * @var integer
     */
    private $min_word_length = 0;

    /**
     * @var string
     */
    private $language;
    private $left_min_hyphen = 2;
    private $right_min_hyphen = 2;
    private $patterns = null;
    private $max_pattern = null;
    private $hyphenation = null;

    /**
     * Character encoding to use.
     *
     * @var string|null
     */
    private static $encoding = 'UTF-8';
    private static $cache_dir = null;
    private static $language_dir = null;
    private $excludes = array();
    private $includes = array();

    /**
     * @var int
     */
    private $libxmlOptions = 0;

    /**
     * Create a new Syllable class, with defaults
     *
     * @param string $language
     * @param string|Hyphen $hyphen
     */
    public function __construct($language = 'en', $hyphen = null)
    {
        if (!self::$cache_dir) {
            self::$cache_dir = __DIR__ . '/../cache';
        }
        $this->setCache(new Json(self::$cache_dir));

        if (!self::$language_dir) {
            self::$language_dir = __DIR__ . '/../languages';
        }

        $this->setLanguage($language);

        $this->setHyphen($hyphen ? $hyphen : new Soft());
    }

    /**
     * Set the directory where compiled language files may be stored.
     * Default to the `cache` subdirectory of the current directory.
     *
     * @param string $dir
     */
    public static function setCacheDir($dir)
    {
        self::$cache_dir = $dir;
    }

    /**
     * Set the character encoding to use.
     * Specify `null` encoding to not apply any encoding at all.
     *
     * @param string|null $encoding Either name of encoding or null to disable
     */
    public static function setEncoding($encoding = null)
    {
        self::$encoding = $encoding;
    }

    /**
     * Set the directory where language source files can be found.
     * Default to the `languages` subdirectory of the current directory.
     *
     * @param string $dir
     */
    public static function setLanguageDir($dir)
    {
        self::$language_dir = $dir;
    }

    /**
     * Set the language whose rules will be used for hyphenation.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        $this->setSource(new File($language, self::$language_dir));
    }

    /**
     * Set the hyphen text or object to use as a hyphen marker.
     *
     * @param Mixed $hyphen either a Syllable_Hyphen_Interface or a string, which is turned into a Syllable_Hyphen_Text
     */
    public function setHyphen($hyphen)
    {
        $this->Hyphen = ($hyphen instanceof Hyphen) ? $hyphen : new Text($hyphen);
    }

    /**
     * Get the current hyphen object.
     *
     * @return Hyphen hyphen
     */
    public function getHyphen()
    {
        return $this->Hyphen;
    }

    /**
     * @param Cache $Cache
     */
    public function setCache(Cache $Cache = null)
    {
        $this->Cache = $Cache;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->Cache;
    }

    public function setSource(Source $Source)
    {
        $this->Source = $Source;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->Source;
    }

    /**
     * Words need to contain at least this many character to be hyphenated.
     *
     * @param integer $length
     */
    public function setMinWordLength($length = 0)
    {
        $this->min_word_length = $length;
    }

    /**
     * @return integer
     */
    public function getMinWordLength()
    {
        return $this->min_word_length;
    }

    /**
     * Options to use for HTML parsing by libxml
     * @param integer $libxmlOptions
     * @see https://www.php.net/manual/de/libxml.constants.php 
     */
    public function setLibxmlOptions($libxmlOptions)
    {
        $this->libxmlOptions = $libxmlOptions;
    }

    private static function initEncoding()
    {
        if (self::$encoding) {
            mb_internal_encoding(self::$encoding);
            mb_regex_encoding(self::$encoding);
        }
    }

    /**
     * Ensure the language is loaded into cache
     */
    private function loadLanguage()
    {
        $loaded = false;

        $cache = $this->getCache();
        if ($cache !== null) {
            $cache->open($this->language);

            if (isset($cache->version) && $cache->version == self::CACHE_VERSION && isset($cache->patterns) && isset($cache->max_pattern) && isset($cache->hyphenation) && isset($cache->left_min_hyphen) && isset($cache->right_min_hyphen)) {
                $this->patterns = $cache->patterns;
                $this->max_pattern = $cache->max_pattern;
                $this->hyphenation = $cache->hyphenation;
                $this->left_min_hyphen = $cache->left_min_hyphen;
                $this->right_min_hyphen = $cache->right_min_hyphen;

                $loaded = true;
            }
        }

        if (!$loaded) {
            $source = $this->getSource();
            $this->patterns = $source->getPatterns();
            $this->max_pattern = $source->getMaxPattern();
            $this->hyphenation = $source->getHyphentations();

            $this->left_min_hyphen = 2;
            $this->right_min_hyphen = 2;
            $minHyphens = $source->getMinHyphens();
            if ($minHyphens) {
                $this->left_min_hyphen = $minHyphens[0];
                $this->right_min_hyphen = $minHyphens[1];
            }

            if ($cache !== null) {
                $cache->version = self::CACHE_VERSION;
                $cache->patterns = $this->patterns;
                $cache->max_pattern = $this->max_pattern;
                $cache->hyphenation = $this->hyphenation;
                $cache->left_min_hyphen = $this->left_min_hyphen;
                $cache->right_min_hyphen = $this->right_min_hyphen;

                $cache->close();
            }
        }
    }

    /**
     * Exclude all elements
     */
    public function excludeAll()
    {
        $this->excludes = array('//*');
    }

    /**
     * Add one or more elements to exclude from HTML
     *
     * @param string|string[] $elements
     */
    public function excludeElement($elements)
    {
        foreach ((array)$elements as $element) {
            $this->excludes[] = '//' . $element;
        }
    }

    /**
     * Add one or more elements with attributes to exclude from HTML
     *
     * @param string|string[] $attributes
     * @param string|null $value
     */
    public function excludeAttribute($attributes, $value = null)
    {
        $value = $value === null ? '' : "='{$value}'";

        foreach ((array)$attributes as $attribute) {
            $this->excludes[] = '//*[@' . $attribute . $value . ']';
        }
    }

    /**
     * Add one or more xpath queries to exclude from HTML
     *
     * @param string|string[] $queries
     */
    public function excludeXpath($queries)
    {
        foreach ((array)$queries as $query) {
            $this->excludes[] = $query;
        }
    }

    /**
     * Add one or more elements to include from HTML
     *
     * @param string|string[] $elements
     */
    public function includeElement($elements)
    {
        foreach ((array)$elements as $elements) {
            $this->includes[] = '//' . $elements;
        }
    }

    /**
     * Add one or more elements with attributes to include from HTML
     *
     * @param string|string[] $attributes
     * @param string|null $value
     */
    public function includeAttribute($attributes, $value = null)
    {
        $value = $value === null ? '' : "='{$value}'";

        foreach ((array)$attributes as $attribute) {
            $this->includes[] = '//*[@' . $attribute . $value . ']';
        }
    }

    /**
     * Add one or more xpath queries to include from HTML
     *
     * @param string|string[] $queries
     */
    public function includeXpath($queries)
    {
        foreach ((array)$queries as $query) {
            $this->includes[] = $query;
        }
    }

    /**
     * Split a single word on where the hyphenation would go.
     *
     * @param string $word
     *
     * @return array
     */
    public function splitWord($word)
    {
        self::initEncoding();
        $this->loadLanguage();

        return $this->parseWord($word);
    }

    /**
     * Split a text on where the hyphenation would go.
     *
     * @param string $text
     *
     * @return array
     */
    public function splitText($text)
    {
        self::initEncoding();
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
     *
     * @param string $word
     *
     * @return string
     */
    public function hyphenateWord($word)
    {
        $parts = $this->splitWord($word);
        return $this->Hyphen->joinText($parts);
    }

    /**
     * Hyphenate all words in the plain text.
     *
     * @param string $text
     *
     * @return string
     */
    public function hyphenateText($text)
    {
        $parts = $this->splitText($text);
        return $this->Hyphen->joinText($parts);
    }

    /**
     * Hyphenate all readable text in the HTML, excluding HTML tags and
     * attributes.
     *
     * @param string $html
     *
     * @return string
     */
    public function hyphenateHtml($html)
    {
        $dom = new \DOMDocument();
        $dom->resolveExternals = true;
        $dom->loadHTML($html, $this->libxmlOptions);

        // filter excludes
        $xpath = new \DOMXPath($dom);
        $excludedNodes = $this->excludes ? $xpath->query(join('|', $this->excludes)) : null;
        $includedNodes = $this->includes ? $xpath->query(join('|', $this->includes)) : null;

        $this->hyphenateHtmlDom($dom, $excludedNodes, $includedNodes);

        return $dom->saveHTML();
    }

    /**
     * Add hyphenation to the DOM nodes
     *
     * @param \DOMNode $node
     * @param \DOMNodeList|null $excludeNodes
     * @param \DOMNodeList|null $includeNodes
     * @param bool $split
     */
    private function hyphenateHtmlDom(
        \DOMNode $node,
        \DOMNodeList $excludeNodes = null,
        \DOMNodeList $includeNodes = null,
        $split = true
    )
    {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $split_child = $split;
                if ($excludeNodes && self::hasNode($child, $excludeNodes)) {
                    $split_child = false;
                }
                if ($includeNodes && self::hasNode($child, $includeNodes)) {
                    $split_child = true;
                }

                $this->hyphenateHtmlDom($child, $excludeNodes, $includeNodes, $split_child);
            }
        }

        if ($split && $node instanceof \DOMText) {
            $parts = $this->splitText($node->data);

            $this->Hyphen->joinHtmlDom($parts, $node);
        }
    }

    /**
     * Test if the node is known
     *
     * @param \DOMNode $node
     * @param \DOMNodeList $nodes
     *
     * @return boolean
     */
    private static function hasNode(\DOMNode $node, \DOMNodeList $nodes)
    {
        foreach ($nodes as $test) {
            if ($node->isSameNode($test)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Count the number of syllables in the text and return a map with
     * syllable count as key and number of words for that syllable count as
     * the value.
     *
     * @param string $text
     *
     * @return array
     */
    public function histogramText($text)
    {
        self::initEncoding();
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
     *
     * @param string $text
     *
     * @return int
     */
    public function countWordsText($text)
    {
        self::initEncoding();
        $this->loadLanguage();

        $count = 0;
        foreach (mb_split('[^\'[:alpha:]]+', $text) as $word) {
            if (mb_strlen($word)) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Count the number of syllables in the text.
     *
     * @param string $text
     *
     * @return int
     */
    public function countSyllablesText($text)
    {
        self::initEncoding();
        $this->loadLanguage();

        $count = 0;
        foreach (mb_split('[^\'[:alpha:]]+', $text) as $word) {
            if (mb_strlen($word)) {
                $count += count($this->parseWord($word));
            }
        }

        return $count;
    }

    /**
     * Count the number of polysyllables in the text.
     *
     * @param string $text
     *
     * @return int
     */
    public function countPolysyllablesText($text)
    {
        self::initEncoding();
        $this->loadLanguage();

        $count = 0;
        foreach (mb_split('[^\'[:alpha:]]+', $text) as $split) {
            if (mb_strlen($split) && count($this->parseWord($split)) >= 3) {
                ++$count;
            }
        }

        return $count;
    }

    /**
     * Splits a word into an array of syllables.
     *
     * @param string $word the word to be split.
     *
     * @return array array of syllables.
     */
    private function parseWord($word)
    {
        $word_length = mb_strlen($word);

        // Is this word smaller than the miminal length requirement?
        if ($word_length < $this->left_min_hyphen + $this->right_min_hyphen || $word_length < $this->min_word_length) {
            return array($word);
        }

        // Is it a pre-hyphenated word?
        if (isset($this->hyphenation[$word])) {
            return mb_split('-', $this->hyphenation[$word]);
        }

        // Convenience array
        $text = '.' . mb_strtolower($word) . '.';
        $text_length = $word_length + 2;
        $pattern_length = $this->max_pattern < $text_length ? $this->max_pattern : $text_length;

        // Maximize
        $before = array();
        $end = $text_length - $this->right_min_hyphen;
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
        $part = mb_substr($word, 0, $this->left_min_hyphen);
        for ($i = $this->left_min_hyphen + 1; $i < $end; ++$i) {
            if (isset($before[$i])) {
                $score = (int)$before[$i];
                if ($score & 1) { // only odd
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
