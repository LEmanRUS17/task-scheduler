<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Cache;

use App\Shared\Cache\RedisCacheStore;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class RedisCacheStoreTest extends TestCase
{
    public function testGetDelegatesToCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('get')->with('key-1')->willReturn('value-1');

        $this->assertSame('value-1', (new RedisCacheStore($cache))->get('key-1'));
    }

    public function testSetDelegatesToCacheWithTtl(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('set')->with('key-1', 'value-1', 60);

        (new RedisCacheStore($cache))->set('key-1', 'value-1', 60);
    }

    public function testDeleteDelegatesToCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())->method('delete')->with('key-1');

        (new RedisCacheStore($cache))->delete('key-1');
    }
}
