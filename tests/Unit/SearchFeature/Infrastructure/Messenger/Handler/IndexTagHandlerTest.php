<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\Handler;

use App\SearchFeature\Domain\Port\TagSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Handler\IndexTagHandler;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTagMessage;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use PHPUnit\Framework\TestCase;

final class IndexTagHandlerTest extends TestCase
{
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->createdAt = new \DateTimeImmutable('2024-01-01 13:00:00');
    }

    private function makeTag(
        string $id = 'tag-uuid',
        string $name = 'urgent',
        ?string $description = 'High priority',
        string $ownerId = 'user-1',
    ): TagResponseInterface {
        $tag = $this->createStub(TagResponseInterface::class);
        $tag->method('getId')->willReturn($id);
        $tag->method('getName')->willReturn($name);
        $tag->method('getDescription')->willReturn($description);
        $tag->method('getOwnerId')->willReturn($ownerId);
        $tag->method('getCreatedAt')->willReturn($this->createdAt);

        return $tag;
    }

    public function testIndexesTagWhenFound(): void
    {
        $tagService = $this->createStub(TagServiceInterface::class);
        $tagService->method('getById')->willReturn($this->makeTag());

        $searchIndex = $this->createMock(TagSearchIndexInterface::class);
        $searchIndex->expects($this->once())
            ->method('index')
            ->with('tag-uuid', 'urgent', 'High priority', 'user-1', $this->createdAt);
        $searchIndex->expects($this->never())->method('delete');

        (new IndexTagHandler($tagService, $searchIndex))(new IndexTagMessage('tag-uuid'));
    }

    public function testIndexesTagWithEmptyDescriptionWhenNull(): void
    {
        $tagService = $this->createStub(TagServiceInterface::class);
        $tagService->method('getById')->willReturn($this->makeTag(description: null));

        $searchIndex = $this->createMock(TagSearchIndexInterface::class);
        $searchIndex->expects($this->once())
            ->method('index')
            ->with('tag-uuid', 'urgent', '', 'user-1', $this->createdAt);

        (new IndexTagHandler($tagService, $searchIndex))(new IndexTagMessage('tag-uuid'));
    }

    public function testDeletesFromIndexWhenTagNotFound(): void
    {
        $tagService = $this->createStub(TagServiceInterface::class);
        $tagService->method('getById')->willReturn(null);

        $searchIndex = $this->createMock(TagSearchIndexInterface::class);
        $searchIndex->expects($this->never())->method('index');
        $searchIndex->expects($this->once())->method('delete')->with('tag-uuid');

        (new IndexTagHandler($tagService, $searchIndex))(new IndexTagMessage('tag-uuid'));
    }
}
