<?php

declare(strict_types=1);

namespace App\TeamFeature\Application\ApiService;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\TeamFeature\Application\DataMapper\TeamDataMapper;
use App\TeamFeature\Domain\Entity\Team;
use App\TeamFeature\Application\DTORequestValidator\TeamValidatorInterface;
use App\TeamFeature\Domain\Interactor\AddTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\RemoveTeamMemberInteractor;
use App\TeamFeature\Domain\Interactor\TeamCreateInteractor;
use App\TeamFeature\Domain\Interactor\TeamUpdateInteractor;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\Repository\TeamRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeatureApi\DTORequest\TeamAddMemberRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use App\TeamFeatureApi\DTORequest\TeamUpdateRequestInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;

final class TeamApiService implements TeamServiceInterface
{
    public function __construct(
        private readonly TeamCreateInteractor $createInteractor,
        private readonly TeamUpdateInteractor $updateInteractor,
        private readonly AddTeamMemberInteractor $addMemberInteractor,
        private readonly RemoveTeamMemberInteractor $removeMemberInteractor,
        private readonly TeamRepositoryInterface $teams,
        private readonly TeamMemberRepositoryInterface $members,
        private readonly TeamDataMapper $dataMapper,
        private readonly TeamValidatorInterface $validator,
        private readonly DescriptionServiceInterface $descriptions,
        private readonly ProfileServiceInterface $profiles,
        private readonly TagServiceInterface $tagService,
    ) {
    }

    public function getList(): array
    {
        return array_map(
            fn($team) => $this->teamToFullResponse($team),
            $this->teams->findAll(),
        );
    }

    public function getTeamsByUserId(string $userId): array
    {
        $members = $this->members->findByUserId($userId);

        $teamIds = array_map(fn($member) => $member->teamId()->value(), $members);

        return array_map(
            fn($team) => $this->teamToFullResponse($team),
            $this->teams->findByIds($teamIds),
        );
    }

    public function getPage(string $userId, int $limit, int $offset): array
    {
        return array_map(
            fn($team) => $this->teamToFullResponse($team),
            $this->teams->findPaginatedByMemberUserId($userId, $limit, $offset),
        );
    }

    public function countAll(string $userId): int
    {
        return $this->teams->countByMemberUserId($userId);
    }

    public function getByIds(array $ids): array
    {
        $byId = [];
        foreach ($this->teams->findByIds($ids) as $team) {
            $byId[$team->id()->value()] = $team;
        }

        $result = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $result[] = $this->teamToFullResponse($byId[$id]);
            }
        }

        return $result;
    }

    public function getById(string $id): ?TeamDataResponseInterface
    {
        $team = $this->teams->findById(TeamId::fromString($id));

        return $team !== null
            ? $this->dataMapper->teamToResponse(
                $team,
                $this->descriptions->get(Team::class, $id),
            )
            : null;
    }

    public function create(TeamCreateRequestInterface $dtoRequest, string $creatorUserId): TeamDataResponseInterface
    {
        $violations = $this->validator->validate($dtoRequest);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations));
        }

        $tagIds = array_values(array_unique($dtoRequest->getTagIds()));
        $missingTagIds = array_values(array_diff($tagIds, $this->tagService->filterExistingTagIds($tagIds)));
        if ($missingTagIds !== []) {
            throw new \InvalidArgumentException(json_encode([
                'tagIds' => sprintf('Unknown tag ids: %s', implode(', ', $missingTagIds)),
            ]));
        }

        $title = $this->dataMapper->requestToTitle($dtoRequest);
        $team = $this->createInteractor->create($title, $creatorUserId);

        $description = $dtoRequest->getDescription();
        if ($description !== null) {
            $this->descriptions->set(Team::class, $team->id()->value(), $description);
        }

        foreach ($tagIds as $tagId) {
            $this->tagService->assign($tagId, TagServiceInterface::TYPE_TEAM, $team->id()->value(), $creatorUserId);
        }

        return $this->dataMapper->teamToResponse($team, $description);
    }

    public function update(string $id, TeamUpdateRequestInterface $dtoRequest): TeamDataResponseInterface
    {
        $team = $this->updateInteractor->update($id);

        $description = $dtoRequest->getDescription();
        if ($description !== null) {
            $this->descriptions->set(Team::class, $id, $description);
        }

        return $this->dataMapper->teamToResponse(
            $team,
            $this->descriptions->get(Team::class, $id),
        );
    }

    // TODO: a team must not be hard-deleted. Replace with a soft delete: mark the
    //       team with a "deleted" status and forbid every action on the team and
    //       on objects related to it (tasks, members, tags, avatar, etc.).
    public function deleteById(string $id): void
    {
        $team = $this->teams->findById(TeamId::fromString($id));

        if ($team === null) {
            throw new \DomainException("Team {$id} not found");
        }

        $this->teams->delete($team);
        $this->descriptions->delete(Team::class, $id);
    }

    public function getMembers(string $teamId): array
    {
        return array_map(
            fn($member) => $this->dataMapper->memberToResponse(
                $member,
                $this->findProfile($member->userId()),
            ),
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

        return $this->dataMapper->memberToResponse($member, $this->findProfile($member->userId()));
    }

    private function findProfile(string $userId): ?ProfileDataResponseInterface
    {
        try {
            return $this->profiles->getByUserId($userId);
        } catch (\DomainException) {
            return null;
        }
    }

    public function removeMember(string $teamId, string $userId): void
    {
        $this->removeMemberInteractor->remove(TeamId::fromString($teamId), $userId);
    }

    private function teamToFullResponse(Team $team): TeamDataResponseInterface
    {
        return $this->dataMapper->teamToResponse(
            $team,
            $this->descriptions->get(Team::class, $team->id()->value()),
        );
    }
}
