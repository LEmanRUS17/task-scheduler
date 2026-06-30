<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\Handler;

use App\SearchFeature\Domain\Port\TagSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTagMessage;
use App\TagFeatureApi\Contract\TagServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class IndexTagHandler
{
    public function __construct(
        private readonly TagServiceInterface $tagService,
        private readonly TagSearchIndexInterface $searchIndex,
    ) {
    }

    public function __invoke(IndexTagMessage $message): void
    {
        $tag = $this->tagService->getById($message->tagId);

        // The tag no longer exists (e.g. it was deleted): drop it from the index.
        if ($tag === null) {
            $this->searchIndex->delete($message->tagId);

            return;
        }

        $this->searchIndex->index(
            $tag->getId(),
            $tag->getName(),
            $tag->getDescription() ?? '',
            $tag->getOwnerId(),
            $tag->getCreatedAt(),
        );
    }
}
