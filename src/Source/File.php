<?php

namespace Vanderlee\Syllable\Source;

/**
 * Default language strategy tries to load TeX files from a relative path
 * to the class sourcefile.
 */
class File implements Source
{
    private static $minHyphens = null;
    private $path = null;
    private $language = null;
    private $loaded = false;
    private $patterns = null;
    private $max_pattern_length = null;
    private $hyphenations = null;

    public function __construct($language, $path)
    {
        $this->setLanguage($language);
        $this->setPath($path);
        $this->loaded = false;
    }

    public function setPath($path)
    {
        $this->path = $path;
        $this->loaded = false;
    }

    public function setLanguage($language)
    {
        $this->language = strtolower($language);
        $this->loaded = false;
    }

    public function getMinHyphens()
    {
        if (!self::$minHyphens) {
            self::$minHyphens = json_decode(file_get_contents("{$this->path}/min.json"), true);
        }

        return isset(self::$minHyphens[$this->language]) ? self::$minHyphens[$this->language] : null;
    }

    private function loadLanguage()
    {
        if (!$this->loaded) {
            $this->patterns = [];
            $this->max_pattern_length = 0;
            $this->hyphenations = [];

            // parser state
            $command = false;
            $braces = false;

            // parse .tex file
            foreach (file("{$this->path}/hyph-{$this->language}.tex") as $line) {
                $offset = 0;
                $strlen_line = mb_strlen($line);
                while ($offset < $strlen_line) {
                    $char = $line[$offset];

                    // %comment
                    if ($char === '%') {
                        break; // ignore rest of line
                    }

                    // \command
                    if ($char === '\\' && preg_match('~^\\\\([[:alpha:]]+)~', mb_substr($line, $offset), $m) === 1) {
                        $command = $m[1];
                        $offset += mb_strlen($m[0]);
                        continue; // next token
                    }

                    // {
                    if ($char === '{') {
                        $braces = true;
                        $offset++;
                        continue; // next token
                    }

                    // content
                    if ($braces) {
                        switch ($command) {
                            case 'patterns':
                                if (preg_match('~^\S+~u', mb_substr($line, $offset), $m) === 1) {
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
                                            $strlen++;
                                            $expect_number = true;
                                        }
                                        $offset++;
                                    }
                                    if ($expect_number) {
                                        $numbers .= '0';
                                    }

                                    $this->patterns[$pattern] = $numbers;
                                    if ($strlen > $this->max_pattern_length) {
                                        $this->max_pattern_length = $strlen;
                                    }
                                }
                                continue 3; // next token

                            case 'hyphenation':
                                if (preg_match('~^\S+~u', substr($line, $offset), $m) === 1) {
                                    $hyphenation = preg_replace('~\-~', '', $m[0]);
                                    $this->hyphenations[$hyphenation] = $m[0];
                                    $offset += strlen($m[0]);
                                }
                                continue 3; // next token
                        }
                    }

                    // }
                    if ($char === '}') {
                        $braces = false;
                        $command = false;
                        $offset++;
                        continue; // next token
                    }

                    // ignorable content, skip one char
                    $offset++;
                }
            }

            $this->loaded = true;
        }
    }

    public function getHyphentations()
    {
        $this->loadLanguage();

        return $this->hyphenations;
    }

    public function getMaxPattern()
    {
        $this->loadLanguage();

        return $this->max_pattern_length;
    }

    public function getPatterns()
    {
        $this->loadLanguage();

        return $this->patterns;
    }
}
