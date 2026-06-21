<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\Service;

use App\TeamFeatureApi\DTORequest\TeamAddMemberRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamUpdateRequestInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface as ResponseDTO;
use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface as MemberResponseDTO;

interface TeamServiceInterface
{
    /** @return ResponseDTO[] */
    public function getList(): array;

    /** @return ResponseDTO[] */
    public function getTeamsByUserId(string $userId): array;

    /**
     * Returns a single page of the user's teams ordered by creation date (newest first).
     *
     * @return ResponseDTO[]
     */
    public function getPage(string $userId, int $limit, int $offset): array;

    public function countAll(string $userId): int;

    /**
     * Returns teams for the given ids, preserving the order of the ids.
     *
     * @param list<string> $ids
     * @return ResponseDTO[]
     */
    public function getByIds(array $ids): array;

    public function getById(string $id): ?ResponseDTO;

    public function create(TeamCreateRequestInterface $dtoRequest, string $creatorUserId): ResponseDTO;

    public function update(string $id, TeamUpdateRequestInterface $dtoRequest): ResponseDTO;

    public function deleteById(string $id): void;

    /** @return MemberResponseDTO[] */
    public function getMembers(string $teamId): array;

    public function addMember(string $teamId, TeamAddMemberRequestInterface $request): MemberResponseDTO;

    public function removeMember(string $teamId, string $userId): void;
}
