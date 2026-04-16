<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence;

use App\TeamFeature\Domain\Entity\TeamMember;
use App\TeamFeature\Domain\Repository\TeamMemberRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
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

    public function findByTeamId(TeamId $teamId): array
    {
        return $this->entityManager->getRepository(TeamMember::class)->findBy([
            'teamId' => $teamId->value(),
        ]);
    }

    public function findByTeamAndUser(TeamId $teamId, string $userId): ?TeamMember
    {
        return $this->entityManager->getRepository(TeamMember::class)->findOneBy([
            'teamId' => $teamId->value(),
            'userId' => $userId,
        ]);
    }

    public function delete(TeamMember $member): void
    {
        $this->entityManager->remove($member);
        $this->entityManager->flush();
    }
}
