<?php

namespace Vanderlee\Syllable\Cache;

/**
 * Single-file cache using JSON format to encode data.
 */
class Json extends File
{
    protected function encode($array)
    {
        return json_encode($array);
    }

    protected function decode($array)
    {
        return json_decode($array, true);
    }

    protected function getFilename($language)
    {
        return "syllable.$language.json";
    }
}
