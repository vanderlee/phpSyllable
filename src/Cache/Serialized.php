<?php

namespace Vanderlee\Syllable\Cache;

/**
 * Single-file cache using PHP-native serialization to encode data.
 */
class Serialized extends File
{
    protected function encode($array)
    {
        return serialize($array);
    }

    protected function decode($array)
    {
        return unserialize($array);
    }

    protected function getFilename($language)
    {
        return "syllable.$language.serialized";
    }
}
