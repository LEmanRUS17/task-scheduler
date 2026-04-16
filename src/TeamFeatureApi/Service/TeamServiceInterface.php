<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\Service;

use App\TeamFeatureApi\DTORequest\TeamAddMemberRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface as ResponseDTO;
use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface as MemberResponseDTO;

interface TeamServiceInterface
{
    /** @return ResponseDTO[] */
    public function getList(): array;

    public function getById(string $id): ?ResponseDTO;

    public function create(TeamCreateRequestInterface $dtoRequest, string $creatorUserId): ResponseDTO;

    public function deleteById(string $id): void;

    /** @return MemberResponseDTO[] */
    public function getMembers(string $teamId): array;

    public function addMember(string $teamId, TeamAddMemberRequestInterface $request): MemberResponseDTO;

    public function removeMember(string $teamId, string $userId): void;
}
