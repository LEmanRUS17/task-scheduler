<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Messenger\Handler;

use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\SearchFeature\Domain\Port\UserSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexUserMessage;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class IndexUserHandler
{
    public function __construct(
        private readonly ProfileServiceInterface $profileService,
        private readonly UserServiceInterface $userService,
        private readonly TeamServiceInterface $teamService,
        private readonly UserSearchIndexInterface $searchIndex,
    ) {
    }

    public function __invoke(IndexUserMessage $message): void
    {
        $user = $this->userService->findById($message->userId);

        if ($user === null) {
            return;
        }

        try {
            $profile = $this->profileService->getByUserId($message->userId);
        } catch (\DomainException) {
            return;
        }

        $teamIds = array_values(array_map(
            static fn($team) => $team->getId(),
            $this->teamService->getTeamsByUserId($message->userId),
        ));

        $this->searchIndex->index(
            $message->userId,
            $profile->getUsername() ?? '',
            $user->getEmail(),
            $profile->getFirstname() ?? '',
            $profile->getLastname() ?? '',
            $profile->getMidlname() ?? '',
            $teamIds,
        );
    }
}
