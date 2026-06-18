<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Indexing;

use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface;

/**
 * Resolves the owner (creator) of a team from its member list.
 *
 * The team creator is persisted as the member holding the OWNER role,
 * so the index reuses that as the `created_by` attribute.
 */
final class TeamOwnerResolver
{
    private const OWNER_ROLE = 'owner';

    /** @param list<TeamMemberDataResponseInterface> $members */
    public static function resolve(array $members): string
    {
        foreach ($members as $member) {
            if ($member->getRole() === self::OWNER_ROLE) {
                return $member->getUserId();
            }
        }

        return '';
    }
}
