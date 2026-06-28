<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\Handler;

use App\SearchFeature\Domain\Port\TeamSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexTeamMessage;
use App\SearchFeature\Infrastructure\Indexing\TeamOwnerResolver;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class IndexTeamHandler
{
    public function __construct(
        private readonly TeamServiceInterface $teamService,
        private readonly TeamSearchIndexInterface $searchIndex,
        private readonly TagServiceInterface $tagService,
    ) {
    }

    public function __invoke(IndexTeamMessage $message): void
    {
        $team = $this->teamService->getById($message->teamId);

        if ($team === null) {
            return;
        }

        $members = $this->teamService->getMembers($message->teamId);

        $memberIds = array_values(array_map(
            static fn($member) => $member->getUserId(),
            $members,
        ));

        $tagNames = $this->tagService->getEntityTagNames(TagServiceInterface::TYPE_TEAM, $team->getId());
        $tags = implode(' ', $tagNames);

        $this->searchIndex->index(
            $team->getId(),
            $team->getTitle(),
            $team->getStatus(),
            TeamOwnerResolver::resolve($members),
            $team->getCreatedAt(),
            $memberIds,
            $tags,
        );
    }
}
