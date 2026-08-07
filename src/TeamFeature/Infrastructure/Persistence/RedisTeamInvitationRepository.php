<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence;

use App\Shared\Cache\CacheStoreInterface;
use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\Repository\TeamInvitationRepositoryInterface;
use App\TeamFeature\Domain\ValueObject\PendingTeamInvitation;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;

/**
 * Stores invitations in Redis instead of a relational table. Only what's
 * needed to redeem the token is kept — team id, invited user id, role —
 * keyed by the token itself, with TTL matching the invitation's expiry so
 * unused tokens disappear on their own. Accepted tokens are deleted
 * explicitly (see delete()), making them single-use.
 */
final class RedisTeamInvitationRepository implements TeamInvitationRepositoryInterface
{
    private const string KEY_PREFIX = 'team_invitation_';
    private const string PENDING_INDEX_PREFIX = 'team_invitation_pending_';

    public function __construct(
        private readonly CacheStoreInterface $cache,
    ) {
    }

    public function save(TeamInvitation $invitation): void
    {
        $ttl = max(1, $invitation->expiresAt()->getTimestamp() - time());
        $teamId = $invitation->teamId()->value();
        $userId = $invitation->invitedUserId();

        $this->cache->set(self::KEY_PREFIX . $invitation->token(), [
            'teamId' => $teamId,
            'userId' => $userId,
            'role' => $invitation->role()->value,
        ], $ttl);

        $this->cache->set(self::pendingIndexKey($teamId, $userId), $invitation->token(), $ttl);
    }

    public function findByToken(string $token): ?PendingTeamInvitation
    {
        $data = $this->cache->get(self::KEY_PREFIX . $token);

        if (!\is_array($data)) {
            return null;
        }

        return new PendingTeamInvitation(
            TeamId::fromString($data['teamId']),
            $data['userId'],
            TeamMemberRole::from($data['role']),
        );
    }

    public function hasPendingInvitation(TeamId $teamId, string $userId): bool
    {
        return $this->cache->get(self::pendingIndexKey($teamId->value(), $userId)) !== null;
    }

    public function delete(string $token, TeamId $teamId, string $invitedUserId): void
    {
        $this->cache->delete(self::KEY_PREFIX . $token);
        $this->cache->delete(self::pendingIndexKey($teamId->value(), $invitedUserId));
    }

    private static function pendingIndexKey(string $teamId, string $userId): string
    {
        return self::PENDING_INDEX_PREFIX . $teamId . '_' . $userId;
    }
}
