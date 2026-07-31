<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence;

use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\Repository\TeamInvitationRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamInvitationStatus;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTeamInvitationRepository implements TeamInvitationRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(TeamInvitation $invitation): void
    {
        $this->entityManager->persist($invitation);
        $this->entityManager->flush();
    }

    public function findByToken(string $token): ?TeamInvitation
    {
        return $this->entityManager->getRepository(TeamInvitation::class)->findOneBy([
            'token' => $token,
        ]);
    }

    public function findPendingByTeamAndUser(TeamId $teamId, string $userId): ?TeamInvitation
    {
        return $this->entityManager->getRepository(TeamInvitation::class)->findOneBy([
            'teamId' => $teamId->value(),
            'invitedUserId' => $userId,
            'status' => TeamInvitationStatus::PENDING,
        ]);
    }
}
