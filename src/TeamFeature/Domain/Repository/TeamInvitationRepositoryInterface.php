<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Repository;

use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\ValueObject\TeamId;

interface TeamInvitationRepositoryInterface
{
    public function save(TeamInvitation $invitation): void;

    public function findByToken(string $token): ?TeamInvitation;

    public function findPendingByTeamAndUser(TeamId $teamId, string $userId): ?TeamInvitation;
}
