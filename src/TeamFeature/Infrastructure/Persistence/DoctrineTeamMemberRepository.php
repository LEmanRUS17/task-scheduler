<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use Doctrine\ORM\EntityManagerInterface;


final class DoctrineTeamMemberRepository implements TeamMemberRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function save(TeamMember $member): void
    {
        $this->entityManager->persist($member);
        $this->entityManager->flush();
    }

    /** @return list<TeamMember> */
    public function findByTeamId(TeamId $teamId): array
    {
        return $this->entityManager->getRepository(TeamMember::class)->findBy([
            'teamId' => $teamId->value(),
        ]);
    }

    /** @return list<TeamMember> */
    public function findByUserId(string $userId): array
    {
        return $this->entityManager->getRepository(TeamMember::class)->findBy([
            'userId' => $userId,
        ]);
    }

    public function findByTeamAndUser(TeamId $teamId, string $userId): ?TeamMember
    {
        return $this->entityManager->getRepository(TeamMember::class)->findOneBy([
            'teamId' => $teamId->value(),
            'userId' => $userId,
        ]);
    }

    public function findByTeamAndUserAndRole(
        TeamId $teamId,
        string $userId,
        TeamMemberRole $role
    ): ?TeamMember {
        return $this->entityManager->getRepository(TeamMember::class)->findOneBy([
            'teamId' => $teamId->value(),
            'userId' => $userId,
            'role' => $role
        ]);
    }

    public function delete(TeamMember $member): void
    {
        $this->entityManager->remove($member);
        $this->entityManager->flush();
    }
}
