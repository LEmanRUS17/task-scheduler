<?php

declare(strict_types=1);

namespace App\SearchFeature\Application\ApiService;

use App\SearchFeature\Domain\Port\TagSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\TeamSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\UserSearchRepositoryInterface;
use App\SearchFeature\Domain\Port\WorkflowSearchRepositoryInterface;
use App\SearchFeatureApi\Contract\SearchServiceInterface;

final class SearchApiService implements SearchServiceInterface
{
    public function __construct(
        private readonly TaskSearchRepositoryInterface $repository,
        private readonly TeamSearchRepositoryInterface $teamRepository,
        private readonly WorkflowSearchRepositoryInterface $workflowRepository,
        private readonly TagSearchRepositoryInterface $tagRepository,
        private readonly UserSearchRepositoryInterface $userRepository,
    ) {
        return;
    }


    /** @return array{ids: list<string>, total: int} */
    public function searchTasks(
        string $query,
        string $userId,
        ?string $teamId = null,
        ?string $status = null,
        int $limit = 10,
        int $offset = 0,
    ): array {
        return $this->repository->search($query, $userId, $teamId, $status, $limit, $offset);
    }

    /**
     * @param list<string> $statuses
     * @return array{ids: list<string>, total: int}
     */
    public function searchTeams(
        string $query,
        string $userId,
        array $statuses = [],
        bool $ownedOnly = false,
        int $limit = 10,
        int $offset = 0,
    ): array {
        return $this->teamRepository->search($query, $userId, $statuses, $ownedOnly, $limit, $offset);
    }

    /** @return array{ids: list<string>, total: int} */
    public function searchWorkflows(
        string $query,
        string $userId,
        bool $ownedOnly = false,
        int $limit = 10,
        int $offset = 0,
    ): array {
        return $this->workflowRepository->search($query, $userId, $ownedOnly, $limit, $offset);
    }

    /** @return array{ids: list<string>, total: int} */
    public function searchTags(
        string $query,
        string $userId,
        int $limit = 10,
        int $offset = 0,
    ): array {
        return $this->tagRepository->search($query, $userId, $limit, $offset);
    }

    /** @return array{ids: list<string>, total: int} */
    public function searchTeamUsers(
        string $teamId,
        string $query,
        int $limit = 50,
        int $offset = 0,
    ): array {
        return $this->userRepository->searchInTeam($teamId, $query, $limit, $offset);
    }
}
