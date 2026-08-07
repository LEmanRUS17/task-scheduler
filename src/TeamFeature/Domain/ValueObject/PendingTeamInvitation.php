<?php

declare(strict_types=1);

namespace App\TeamFeature\Domain\ValueObject;

/**
 * The minimal set of facts needed to redeem an invitation token: which team,
 * which user it was addressed to, and which role they'd join with.
 */
final readonly class PendingTeamInvitation
{
    public function __construct(
        public TeamId $teamId,
        public string $invitedUserId,
        public TeamMemberRole $role,
    ) {
    }
}
