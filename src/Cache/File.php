<?php

namespace Vanderlee\Syllable\Cache;

abstract class File implements Cache
{
    private static $language = null;
    private static $path = null;
    private static $data = null;

    abstract protected function encode($array);

    abstract protected function decode($array);

    abstract protected function getFilename($language);

    public function __construct($path)
    {
        $this->setPath($path);
    }

    public function setPath($path)
    {
        if ($path !== self::$path) {
            self::$path = $path;
            self::$data = null;
        }
    }

    private function filename()
    {
        return self::$path.'/'.$this->getFilename(self::$language);
    }

    public function open($language)
    {
        $language = strtolower($language);

        if (self::$language !== $language) {
            self::$language = $language;
            self::$data = null;

            $file = $this->filename();
            if (is_file($file)) {
                self::$data = $this->decode(file_get_contents($file));
            }
        }
    }

    public function close()
    {
        $file = $this->filename();
        file_put_contents($file, $this->encode(self::$data));
        @chmod($file, 0777);
    }

    public function __set($key, $value)
    {
        self::$data[$key] = $value;
    }

    public function __get($key)
    {
        return self::$data[$key];
    }

    public function __isset($key)
    {
        return isset(self::$data[$key]);
    }

    public function __unset($key)
    {
        unset(self::$data[$key]);
    }
}
