<?php

namespace Vanderlee\Syllable;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMText;
use DOMXPath;
use Vanderlee\Syllable\Cache\Cache;
use Vanderlee\Syllable\Cache\Json;
use Vanderlee\Syllable\Hyphen\Hyphen;
use Vanderlee\Syllable\Hyphen\Soft;
use Vanderlee\Syllable\Hyphen\Text;
use Vanderlee\Syllable\Source\File;
use Vanderlee\Syllable\Source\Source;

/**
 * Main class.
 */
class Syllable
{
    /**
     * Version string, used to recalculate language caches if needed.
     */
    const CACHE_VERSION = '1.4';

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Source
     */
    private $source;

    /**
     * @var Hyphen
     */
    private $hyphen;

    /**
     * @var int
     */
    private $minWordLength = 0;

    /**
     * @var string
     */
    private $language;
    private $minHyphenLeft = 2;
    private $minHyphenRight = 2;
    private $patterns = null;
    private $maxPattern = null;
    private $hyphenation = null;

    /**
     * Character encoding to use.
     *
     * @var string|null
     */
    private static $encoding = 'UTF-8';
    private static $cacheDir = null;
    private static $languageDir = null;
    private $excludes = [];
    private $includes = [];

    /**
     * @var int
     */
    private $libxmlOptions = 0;

    /**
     * Create a new Syllable class, with defaults.
     *
     * @param string        $language
     * @param string|Hyphen $hyphen
     */
    public function __construct($language = 'en-us', $hyphen = null)
    {
        if (!self::$cacheDir) {
            self::$cacheDir = __DIR__.'/../cache';
        }
        $this->setCache(new Json(self::$cacheDir));

        if (!self::$languageDir) {
            self::$languageDir = __DIR__.'/../languages';
        }

        $this->setLanguage($language);

        $this->setHyphen($hyphen ?: new Soft());
    }

    /**
     * Set the directory where compiled language files may be stored.
     * Default to the `cache` subdirectory of the current directory.
     *
     * @param string $dir
     */
    public static function setCacheDir($dir)
    {
        self::$cacheDir = $dir;
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
        self::$languageDir = $dir;
    }

    /**
     * Set the language whose rules will be used for hyphenation.
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        $this->setSource(new File($language, self::$languageDir));
    }

    /**
     * Set the hyphen text or object to use as a hyphen marker.
     *
     * @param mixed $hyphen either a Syllable_Hyphen_Interface or a string, which is turned into a Syllable_Hyphen_Text
     */
    public function setHyphen($hyphen)
    {
        $this->hyphen = ($hyphen instanceof Hyphen)
            ? $hyphen
            : new Text($hyphen);
    }

    /**
     * Get the current hyphen object.
     *
     * @return Hyphen hyphen
     */
    public function getHyphen()
    {
        return $this->hyphen;
    }

    /**
     * @param Cache $cache
     */
    public function setCache(Cache $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    public function setSource(Source $source)
    {
        $this->source = $source;
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Words need to contain at least this many character to be hyphenated.
     *
     * @param int $length
     */
    public function setMinWordLength($length = 0)
    {
        $this->minWordLength = $length;
    }

    /**
     * @return int
     */
    public function getMinWordLength()
    {
        return $this->minWordLength;
    }

    /**
     * Options to use for HTML parsing by libxml.
     *
     * @param int $libxmlOptions
     *
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
     * Ensure the language is loaded into cache.
     */
    private function loadLanguage()
    {
        $loaded = false;

        $cache = $this->getCache();
        if ($cache !== null) {
            $cache->open($this->language);

            if (isset($cache->version)
                && $cache->version == self::CACHE_VERSION
                && isset($cache->patterns)
                && isset($cache->max_pattern)
                && isset($cache->hyphenation)
                && isset($cache->left_min_hyphen)
                && isset($cache->right_min_hyphen)) {
                $this->patterns = $cache->patterns;
                $this->maxPattern = $cache->max_pattern;
                $this->hyphenation = $cache->hyphenation;
                $this->minHyphenLeft = $cache->left_min_hyphen;
                $this->minHyphenRight = $cache->right_min_hyphen;

                $loaded = true;
            }
        }

        if (!$loaded) {
            $source = $this->getSource();
            $this->patterns = $source->getPatterns();
            $this->maxPattern = $source->getMaxPattern();
            $this->hyphenation = $source->getHyphentations();

            $this->minHyphenLeft = 2;
            $this->minHyphenRight = 2;
            $minHyphens = $source->getMinHyphens();
            if ($minHyphens) {
                $this->minHyphenLeft = $minHyphens[0];
                $this->minHyphenRight = $minHyphens[1];
            }

            if ($cache !== null) {
                $cache->version = self::CACHE_VERSION;
                $cache->patterns = $this->patterns;
                $cache->max_pattern = $this->maxPattern;
                $cache->hyphenation = $this->hyphenation;
                $cache->left_min_hyphen = $this->minHyphenLeft;
                $cache->right_min_hyphen = $this->minHyphenRight;

                $cache->close();
            }
        }
    }

    /**
     * Exclude all elements.
     */
    public function excludeAll()
    {
        $this->excludes = ['//*'];
    }

    /**
     * Add one or more elements to exclude from HTML.
     *
     * @param string|string[] $elements
     */
    public function excludeElement($elements)
    {
        foreach ((array) $elements as $element) {
            $this->excludes[] = '//'.$element;
        }
    }

    /**
     * Add one or more elements with attributes to exclude from HTML.
     *
     * @param string|string[] $attributes
     * @param string|null     $value
     */
    public function excludeAttribute($attributes, $value = null)
    {
        $value = $value === null ? '' : "='$value'";

        foreach ((array) $attributes as $attribute) {
            $this->excludes[] = '//*[@'.$attribute.$value.']';
        }
    }

    /**
     * Add one or more xpath queries to exclude from HTML.
     *
     * @param string|string[] $queries
     */
    public function excludeXpath($queries)
    {
        foreach ((array) $queries as $query) {
            $this->excludes[] = $query;
        }
    }

    /**
     * Add one or more elements to include from HTML.
     *
     * @param string|string[] $elements
     */
    public function includeElement($elements)
    {
        foreach ((array) $elements as $elements) {
            $this->includes[] = '//'.$elements;
        }
    }

    /**
     * Add one or more elements with attributes to include from HTML.
     *
     * @param string|string[] $attributes
     * @param string|null     $value
     */
    public function includeAttribute($attributes, $value = null)
    {
        $value = $value === null
            ? ''
            : "='$value'";

        foreach ((array) $attributes as $attribute) {
            $this->includes[] = '//*[@'.$attribute.$value.']';
        }
    }

    /**
     * Add one or more xpath queries to include from HTML.
     *
     * @param string|string[] $queries
     */
    public function includeXpath($queries)
    {
        foreach ((array) $queries as $query) {
            $this->includes[] = $query;
        }
    }

    /**
     * Split a single word on where the hyphenation would go.
     *
     * Punctuation is not supported, only simple words. For parsing whole sentences
     * please use Syllable::splitWords() or Syllable::splitText().
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
     * Split a text into an array of punctuation marks and words,
     * splitting each word on where the hyphenation would go.
     *
     * @param string $text
     *
     * @return array
     */
    public function splitWords($text)
    {
        self::initEncoding();
        $this->loadLanguage();

        $words = mb_split('[^\'[:alpha:]]+', $text);
        $parts = [];
        $textPosition = 0;
        $textLength = mb_strlen($text);

        foreach ($words as $word) {
            if (!empty($word)) {
                $wordPosition = mb_stripos($text, $word, $textPosition);

                if ($wordPosition > $textPosition) {
                    $parts[][] = mb_substr($text, $textPosition, $wordPosition - $textPosition);
                }

                $parts[] = $this->parseWord($word);

                $textPosition = $wordPosition + mb_strlen($word);
            }
        }

        if ($textPosition < $textLength - 1) {
            $parts[][] = mb_substr($text, $textPosition);
        }

        return $parts;
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

        $words = mb_split('[^\'[:alpha:]]+', $text);
        $parts = [];
        $part = '';
        $textPosition = 0;

        foreach ($words as $word) {
            if (!empty($word)) {
                $wordPosition = mb_stripos($text, $word, $textPosition);

                if ($wordPosition > $textPosition) {
                    $part .= mb_substr($text, $textPosition, $wordPosition - $textPosition);
                }

                $syllables = $this->parseWord($word);

                $part .= $syllables[0];
                for ($i = 1; $i < count($syllables); $i++) {
                    $parts[] = $part;
                    $part = $syllables[$i];
                }

                $textPosition = $wordPosition + mb_strlen($word);
            }
        }

        $parts[] = $part.mb_substr($text, $textPosition);

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

        return $this->hyphen->joinText($parts);
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

        return $this->hyphen->joinText($parts);
    }

    /**
     * Hyphenate all readable text in the HTML, excluding HTML tags and
     * attributes.
     *
     * @deprecated Use the UTF-8 capable hyphenateHtmlText() instead. This method is kept only for backward compatibility and will be removed in the next major version 2.0.
     *
     * @param string $html
     *
     * @return string
     */
    public function hyphenateHtml($html)
    {
        $dom = new DOMDocument();
        $dom->resolveExternals = true;
        $dom->loadHTML($html, $this->libxmlOptions);

        // filter excludes
        $xpath = new DOMXPath($dom);
        $excludedNodes = $this->excludes ? $xpath->query(join('|', $this->excludes)) : null;
        $includedNodes = $this->includes ? $xpath->query(join('|', $this->includes)) : null;

        $this->hyphenateHtmlDom($dom, $excludedNodes, $includedNodes);

        return $dom->saveHTML();
    }

    /**
     * Hyphenate all readable text in the HTML, excluding HTML tags and
     * attributes.
     *
     * This method is UTF-8 capable and should be preferred over hyphenateHtml().
     *
     * @param string $html
     *
     * @return string
     */
    public function hyphenateHtmlText($html)
    {
        $charset = mb_detect_encoding($html);
        list($bodyContent, $beforeBodyContent, $afterBodyContent) = $this->parseHtmlText($html);
        $html = "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.0 Transitional//EN' 'http://www.w3.org/TR/REC-html40/loose.dtd'>".
            '<html>'.
                '<head>'.
                    "<meta http-equiv='content-type' content='text/html; charset=$charset'>".
                '</head>'.
                "<body>$bodyContent</body>".
            '</html>';

        $dom = new DOMDocument();
        $dom->resolveExternals = true;
        $dom->loadHTML($html, $this->libxmlOptions);

        // filter excludes
        $xpath = new DOMXPath($dom);
        $excludedNodes = $this->excludes ? $xpath->query(join('|', $this->excludes)) : null;
        $includedNodes = $this->includes ? $xpath->query(join('|', $this->includes)) : null;

        $this->hyphenateHtmlDom($dom, $excludedNodes, $includedNodes);

        $hyphenatedBodyContent = $dom->saveHTML($dom->getElementsByTagName('body')->item(0));
        $hyphenatedBodyContent = mb_substr($hyphenatedBodyContent, mb_strlen('<body>'), -mb_strlen('</body>'));
        $hyphenatedHtml = $beforeBodyContent.$hyphenatedBodyContent.$afterBodyContent;

        return $hyphenatedHtml;
    }

    /**
     * @param string $html
     *
     * @return array
     */
    private function parseHtmlText($html)
    {
        if (($bodyContentEnd = mb_strrpos($html, '</body>')) !== false) {
            $bodyContentStart = mb_strpos($html, '<body');
            $bodyContentStart = $bodyContentStart + strcspn($html, '>', $bodyContentStart) + 1;
            $beforeBodyContent = mb_substr($html, 0, $bodyContentStart);
            $afterBodyContent = mb_substr($html, $bodyContentEnd);
            $bodyContent = mb_substr($html, $bodyContentStart, -mb_strlen($afterBodyContent));
        } else {
            $beforeBodyContent = '';
            $afterBodyContent = '';
            $bodyContent = $html;
        }

        return [
            $bodyContent,
            $beforeBodyContent,
            $afterBodyContent,
        ];
    }

    /**
     * Add hyphenation to the DOM nodes.
     *
     * @param DOMNode          $node
     * @param DOMNodeList|null $excludeNodes
     * @param DOMNodeList|null $includeNodes
     * @param bool             $split
     */
    private function hyphenateHtmlDom(
        DOMNode $node,
        DOMNodeList $excludeNodes = null,
        DOMNodeList $includeNodes = null,
        $split = true
    ) {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $splitChild = $split;
                if ($excludeNodes && self::hasNode($child, $excludeNodes)) {
                    $splitChild = false;
                }
                if ($includeNodes && self::hasNode($child, $includeNodes)) {
                    $splitChild = true;
                }

                $this->hyphenateHtmlDom($child, $excludeNodes, $includeNodes, $splitChild);
            }
        }

        if ($split && $node instanceof DOMText) {
            $parts = $this->splitText($node->data);

            $this->hyphen->joinHtmlDom($parts, $node);
        }
    }

    /**
     * Test if the node is known.
     *
     * @param DOMNode     $node
     * @param DOMNodeList $nodes
     *
     * @return bool
     */
    private static function hasNode(DOMNode $node, DOMNodeList $nodes)
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

        $counts = [];
        foreach (mb_split('[^\'[:alpha:]]+', $text) as $split) {
            if (mb_strlen($split)) {
                $count = count($this->parseWord($split));
                if (isset($counts[$count])) {
                    $counts[$count]++;
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
                $count++;
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
                $count++;
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
        $wordLength = mb_strlen($word);

        if ($wordLength < $this->minHyphenLeft + $this->minHyphenRight
            || $wordLength < $this->minWordLength) {
            return [$word];
        }

        $wordLowerCased = mb_strtolower($word);

        if (isset($this->hyphenation[$wordLowerCased])) {
            return $this->parseWordByHyphenation($word, $wordLowerCased);
        } else {
            return $this->parseWordByPatterns($word, $wordLength, $wordLowerCased);
        }
    }

    private function parseWordByHyphenation($word, $wordLowerCased = null)
    {
        $wordLowerCased = $wordLowerCased ?: mb_strtolower($word);

        $hyphenation = $this->hyphenation[$wordLowerCased];
        $hyphenationLength = mb_strlen($hyphenation);

        $parts = [];
        $part = '';
        for ($i = 0, $j = 0; $i < $hyphenationLength; $i++) {
            if (mb_substr($hyphenation, $i, 1) !== '-') {
                $part .= mb_substr($word, $j++, 1);
            } else {
                $parts[] = $part;
                $part = '';
            }
        }
        if (!empty($part)) {
            $parts[] = $part;
        }

        return $parts;
    }

    private function parseWordByPatterns($word, $wordLength = 0, $wordLowerCased = null)
    {
        $wordLength = $wordLength > 0 ? $wordLength : mb_strlen($word);
        $wordLowerCased = $wordLowerCased ?: mb_strtolower($word);

        // Convenience array
        $text = '.'.$wordLowerCased.'.';
        $textLength = $wordLength + 2;
        $patternLength = $this->maxPattern < $textLength
            ? $this->maxPattern
            : $textLength;

        // Maximize
        $before = [];
        $end = $textLength - $this->minHyphenRight;
        for ($start = 0; $start < $end; $start++) {
            $maxLength = $start + $patternLength;
            if ($textLength - $start < $maxLength) {
                $maxLength = $textLength - $start;
            }
            for ($length = 1; $length <= $maxLength; $length++) {
                $subword = mb_substr($text, $start, $length);
                if (isset($this->patterns[$subword])) {
                    $scores = $this->patterns[$subword];
                    $scoresLength = $length + 1;
                    for ($offset = 0; $offset < $scoresLength; $offset++) {
                        $score = $scores[$offset];
                        if (!isset($before[$start + $offset])
                            || $score > $before[$start + $offset]) {
                            $before[$start + $offset] = $score;
                        }
                    }
                }
            }
        }

        // Output
        $parts = [];
        $part = mb_substr($word, 0, $this->minHyphenLeft);
        for ($i = $this->minHyphenLeft + 1; $i < $end; $i++) {
            if (isset($before[$i])) {
                $score = (int) $before[$i];
                if ($score & 1) { // only odd
                    //$part .= $score; // debugging
                    $parts[] = $part;
                    $part = '';
                }
            }
            $part .= mb_substr($word, $i - 1, 1);
        }
        for (; $i < $textLength - 1; $i++) {
            $part .= mb_substr($word, $i - 1, 1);
        }
        if (!empty($part)) {
            $parts[] = $part;
        }

        return $parts;
    }
}
