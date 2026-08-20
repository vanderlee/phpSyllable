<?php

namespace Vanderlee\Syllable\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Adapts a PSR-6 cache pool to the Syllable cache strategy.
 */
class Psr6 implements Cache
{
    /**
     * @var CacheItemPoolInterface
     */
    private $pool;

    /**
     * @var CacheItemInterface|null
     */
    private $item;

    /**
     * @var array
     */
    private $data = [];

    public function __construct(CacheItemPoolInterface $pool)
    {
        $this->pool = $pool;
    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __get($key)
    {
        return $this->data[$key];
    }

    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    public function open($language)
    {
        $this->item = $this->pool->getItem($this->getKey($language));
        $data = $this->item->isHit() ? $this->item->get() : [];
        $this->data = is_array($data) ? $data : [];
    }

    public function close()
    {
        if ($this->item === null) {
            return;
        }

        $this->item->set($this->data);
        $this->pool->save($this->item);
    }

    private function getKey($language)
    {
        return 'syllable.'.sha1(strtolower($language));
    }
}
