<?php

declare(strict_types=1);

namespace App\TaskFeature\Domain\Port;

interface TeamMembershipInterface
{
    public function isMember(string $teamId, string $userId): bool;
}
