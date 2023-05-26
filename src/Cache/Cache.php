<?php

namespace Vanderlee\Syllable\Cache;

/**
 * Defines the Cache strategy interface
 * Create your own caching strategy to store the hyphenation and patterns
 * arrays in the location you want. i.e. from a database or remote server.
 */
interface Cache
{
    public function __set($key, $value);

    public function __get($key);

    public function __isset($key);

    public function __unset($key);

    public function open($language);

    public function close();
}
