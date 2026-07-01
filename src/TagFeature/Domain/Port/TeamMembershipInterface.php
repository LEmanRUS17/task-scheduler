<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Port;

interface TeamMembershipInterface
{
    public function isMember(string $teamId, string $userId): bool;

    /** @return list<string> */
    public function teamIdsOf(string $userId): array;
}
