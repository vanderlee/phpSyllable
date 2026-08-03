<?php

namespace Vanderlee\SyllableTest\Src\Cache;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Vanderlee\Syllable\Cache\Psr6;

class Psr6Test extends TestCase
{
    public function testReadsAndWritesCacheData()
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $item->expects($this->once())
            ->method('get')
            ->willReturn(['version' => '1.4', 'obsolete' => true]);
        $item->expects($this->once())
            ->method('set')
            ->with(['version' => '1.4', 'patterns' => ['example'], 'max_pattern' => 7])
            ->willReturnSelf();

        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects($this->once())
            ->method('getItem')
            ->with('syllable.'.sha1('en-us'))
            ->willReturn($item);
        $pool->expects($this->once())
            ->method('save')
            ->with($item)
            ->willReturn(true);

        $cache = new Psr6($pool);
        $cache->open('EN-US');

        $this->assertTrue(isset($cache->version));
        $this->assertSame('1.4', $cache->version);

        unset($cache->obsolete);
        $cache->patterns = ['example'];
        $cache->max_pattern = 7;
        $cache->close();
    }

    public function testCacheMissStartsWithEmptyData()
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $item->expects($this->never())
            ->method('get');

        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->expects($this->once())
            ->method('getItem')
            ->willReturn($item);

        $cache = new Psr6($pool);
        $cache->open('en-us');

        $this->assertFalse(isset($cache->version));
    }
}
