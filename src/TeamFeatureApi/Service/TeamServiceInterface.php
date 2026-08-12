<?php

declare(strict_types=1);

namespace App\TeamFeatureApi\Service;

use App\TeamFeatureApi\DTORequest\TeamAcceptInvitationRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamAddMemberRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamInviteMemberRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamUpdateRequestInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface as ResponseDTO;
use App\TeamFeatureApi\DTOResponse\TeamInvitationDataResponseInterface as InvitationResponseDTO;
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

    /**
     * Returns the team, verifying that the given user is a member of it.
     *
     * @throws \DomainException if the team does not exist or the user is not a member
     */
    public function getByIdForUser(string $id, string $userId): ResponseDTO;

    public function create(TeamCreateRequestInterface $dtoRequest, string $creatorUserId): ResponseDTO;

    public function update(string $id, TeamUpdateRequestInterface $dtoRequest): ResponseDTO;

    public function deleteById(string $id): void;

    /**
     * Deletes the team, verifying that the given user is its owner.
     *
     * @throws \DomainException if the team does not exist or the user is not its owner
     */
    public function deleteByIdForUser(string $id, string $userId): void;

    /** @return MemberResponseDTO[] */
    public function getMembers(string $teamId): array;

    /** @return list<string> user ids of team members with the owner role */
    public function getOwners(string $teamId): array;

    public function addMember(string $teamId, TeamAddMemberRequestInterface $request): MemberResponseDTO;

    public function removeMember(string $teamId, string $userId): void;

    /**
     * Invites a user (resolved by userId or email) to join the team. An email
     * with the invitation token is sent asynchronously; the user becomes a
     * member only after accepting via {@see self::acceptInvitation()}.
     */
    public function inviteMember(
        string $teamId,
        TeamInviteMemberRequestInterface $request,
        string $invitedByUserId,
    ): InvitationResponseDTO;

    public function acceptInvitation(TeamAcceptInvitationRequestInterface $request, string $userId): MemberResponseDTO;
}
