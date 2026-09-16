<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Persistence;

use App\NotificationFeature\Infrastructure\Persistence\RedisTelegramLinkTokenRepository;
use App\Shared\Cache\CacheStoreInterface;
use PHPUnit\Framework\TestCase;

final class RedisTelegramLinkTokenRepositoryTest extends TestCase
{
    private InMemoryCacheStore $cache;
    private RedisTelegramLinkTokenRepository $repository;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCacheStore();
        $this->repository = new RedisTelegramLinkTokenRepository($this->cache);
    }

    public function testSaveThenFindUserIdByToken(): void
    {
        $this->repository->save('my-token', 'user-9', 3600);

        $this->assertSame('user-9', $this->repository->findUserIdByToken('my-token'));
    }

    public function testFindUserIdByTokenReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repository->findUserIdByToken('unknown-token'));
    }

    public function testDeleteRemovesToken(): void
    {
        $this->repository->save('accept-me', 'user-7', 3600);

        $this->repository->delete('accept-me');

        $this->assertNull($this->repository->findUserIdByToken('accept-me'));
    }

    public function testTtlIsPassedThroughToTheCacheStore(): void
    {
        $cache = $this->createMock(CacheStoreInterface::class);
        $cache->expects($this->once())->method('set')->with('telegram_link_token_my-token', 'user-1', 3600);

        (new RedisTelegramLinkTokenRepository($cache))->save('my-token', 'user-1', 3600);
    }
}
