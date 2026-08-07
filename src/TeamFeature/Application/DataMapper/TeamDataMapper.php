<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DataMapper;

use App\TeamFeature\Application\DTOResponse\TeamInvitationResponseDTO;
use App\TeamFeature\Application\DTOResponse\TeamMemberResponseDTO;
use App\TeamFeature\Application\DTOResponse\TeamResponseDTO;
use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\ValueObject\Title;
use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;

final class TeamDataMapper
{
    public function requestToTitle(TeamCreateRequestInterface $request): Title
    {
        return Title::fromString($request->getTitle());
    }

    public function teamToResponse(Team $team, ?string $description = null): TeamResponseDTO
    {
        return new TeamResponseDTO(
            $team->id()->value(),
            $team->title()->value(),
            $team->status()->value,
            $team->createdAt(),
            $description,
        );
    }

    public function memberToResponse(
        TeamMember $member,
        ?ProfileDataResponseInterface $profile = null,
    ): TeamMemberResponseDTO {
        return new TeamMemberResponseDTO(
            $member->teamId()->value(),
            $member->userId(),
            $member->role()->value,
            $member->joinedAt(),
            $profile,
        );
    }

    public function invitationToResponse(TeamInvitation $invitation): TeamInvitationResponseDTO
    {
        return new TeamInvitationResponseDTO(
            $invitation->id()->value(),
            $invitation->teamId()->value(),
            $invitation->invitedUserId(),
            $invitation->invitedByUserId(),
            $invitation->role()->value,
            $invitation->status()->value,
            $invitation->createdAt(),
            $invitation->expiresAt(),
        );
    }
}
