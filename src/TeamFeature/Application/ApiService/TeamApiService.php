<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\ApiService;

use App\TeamFeature\Application\DataMapper\TeamDataMapper;
use App\TeamFeature\Application\DTORequestValidator\TeamValidatorInterface;
use App\TeamFeature\Domain\Interactor\AddTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\RemoveTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\TeamCreateInteractor;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeatureApi\DTORequest\TeamAddMemberRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;

final class TeamApiService implements TeamServiceInterface
{
    public function __construct(
        private readonly TeamCreateInteractor $createInteractor,
        private readonly AddTeamMemberInteractor $addMemberInteractor,
        private readonly RemoveTeamMemberInteractor $removeMemberInteractor,
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly TeamDataMapper $dataMapper,
        private readonly TeamValidatorInterface $validator,
    ) {}

    public function getList(): array
    {
        return array_map(
            fn($team) => $this->dataMapper->teamToResponse($team),
            $this->teams->findAll(),
        );
    }

    public function getById(string $id): ?TeamDataResponseInterface
    {
        $team = $this->teams->findById(TeamId::fromString($id));

        return $team !== null ? $this->dataMapper->teamToResponse($team) : null;
    }

    public function create(TeamCreateRequestInterface $dtoRequest, string $creatorUserId): TeamDataResponseInterface
    {
        $violations = $this->validator->validate($dtoRequest);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations));
        }

        $title = $this->dataMapper->requestToTitle($dtoRequest);
        $team = $this->createInteractor->create($title, $creatorUserId);

        return $this->dataMapper->teamToResponse($team);
    }

    public function deleteById(string $id): void
    {
        $team = $this->teams->findById(TeamId::fromString($id));

        if ($team === null) {
            throw new \DomainException("Team {$id} not found");
        }

        $this->teams->delete($team);
    }

    public function getMembers(string $teamId): array
    {
        return array_map(
            fn($member) => $this->dataMapper->memberToResponse($member),
            $this->members->findByTeamId(TeamId::fromString($teamId)),
        );
    }

    public function addMember(string $teamId, TeamAddMemberRequestInterface $request): TeamMemberDataResponseInterface
    {
        $member = $this->addMemberInteractor->add(
            TeamId::fromString($teamId),
            $request->getUserId(),
            TeamMemberRole::from($request->getRole()),
        );

        return $this->dataMapper->memberToResponse($member);
    }

    public function removeMember(string $teamId, string $userId): void
    {
        $this->removeMemberInteractor->remove(TeamId::fromString($teamId), $userId);
    }
}
