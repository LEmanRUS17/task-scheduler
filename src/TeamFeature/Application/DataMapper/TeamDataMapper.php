<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\DataMapper;

use App\TeamFeature\Application\DTOResponse\TeamResponseDTO;
use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Domain\ValueObject\Title;
use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;

final class TeamDataMapper
{
    public function requestToTitle(TeamCreateRequestInterface $request): Title
    {
        return Title::fromString($request->getTitle());
    }

    public function teamToResponse(Team $team): TeamResponseDTO
    {
        return new TeamResponseDTO(
            $team->id()->value(),
            $team->title()->value(),
            $team->status()->value,
            $team->createdAt(),
        );
    }
}
