<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\Repository;

use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\ValueObject\PendingTeamInvitation;
use App\TeamFeature\Domain\ValueObject\TeamId;

interface TeamInvitationRepositoryInterface
{
    public function save(TeamInvitation $invitation): void;

    public function findByToken(string $token): ?PendingTeamInvitation;

    public function hasPendingInvitation(TeamId $teamId, string $userId): bool;

    /**
     * Redeems (invalidates) the token after a successful acceptance, so it
     * cannot be replayed.
     */
    public function delete(string $token, TeamId $teamId, string $invitedUserId): void;
}
