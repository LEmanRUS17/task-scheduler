<?php

declare(strict_types=1);

namespace App\Tests\Unit\TeamFeature\Infrastructure\Persistence;

use App\Shared\Cache\RedisCacheStore;
use App\TeamFeature\Domain\Entity\TeamInvitation;
use App\TeamFeature\Domain\ValueObject\TeamId;
use App\TeamFeature\Domain\ValueObject\TeamInvitationId;
use App\TeamFeature\Domain\ValueObject\TeamMemberRole;
use App\TeamFeature\Infrastructure\Persistence\RedisTeamInvitationRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class RedisTeamInvitationRepositoryTest extends TestCase
{
    private InMemoryCacheStore $cache;
    private RedisTeamInvitationRepository $repository;
    private TeamId $teamId;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCacheStore();
        $this->repository = new RedisTeamInvitationRepository($this->cache);
        $this->teamId = TeamId::generate();
    }

    private function makeInvitation(
        string $token = 'valid-token',
        string $invitedUserId = 'user-1',
        TeamMemberRole $role = TeamMemberRole::MEMBER,
    ): TeamInvitation {
        return TeamInvitation::create(
            TeamInvitationId::generate(),
            $this->teamId,
            'Backend',
            $invitedUserId,
            'invitee@example.com',
            'owner-1',
            $role,
            $token,
            new \DateTimeImmutable('2024-01-01 12:00:00'),
            new \DateTimeImmutable('+7 days'),
        );
    }

    public function testSaveThenFindByToken(): void
    {
        $invitation = $this->makeInvitation(token: 'my-token', invitedUserId: 'user-9', role: TeamMemberRole::OWNER);

        $this->repository->save($invitation);
        $found = $this->repository->findByToken('my-token');

        $this->assertNotNull($found);
        $this->assertSame($this->teamId->value(), $found->teamId->value());
        $this->assertSame('user-9', $found->invitedUserId);
        $this->assertSame(TeamMemberRole::OWNER, $found->role);
    }

    public function testFindByTokenReturnsNullWhenMissing(): void
    {
        $this->assertNull($this->repository->findByToken('unknown-token'));
    }

    public function testHasPendingInvitationAfterSave(): void
    {
        $this->repository->save($this->makeInvitation(invitedUserId: 'user-42'));

        $this->assertTrue($this->repository->hasPendingInvitation($this->teamId, 'user-42'));
        $this->assertFalse($this->repository->hasPendingInvitation($this->teamId, 'someone-else'));
    }

    public function testDeleteRemovesTokenAndPendingIndex(): void
    {
        $this->repository->save($this->makeInvitation(token: 'accept-me', invitedUserId: 'user-7'));

        $this->repository->delete('accept-me', $this->teamId, 'user-7');

        $this->assertNull($this->repository->findByToken('accept-me'));
        $this->assertFalse($this->repository->hasPendingInvitation($this->teamId, 'user-7'));
    }

    public function testOnlyTeamIdUserIdAndRoleAreStoredUnderTheTokenKey(): void
    {
        $this->repository->save($this->makeInvitation(token: 'my-token', invitedUserId: 'user-9'));

        $stored = $this->cache->get('team_invitation_my-token');

        $this->assertIsArray($stored);
        $this->assertSame(['teamId', 'userId', 'role'], array_keys($stored));
    }

    /**
     * PSR-16 rejects keys containing "{}()/\@:" (Symfony's Psr16Cache enforces this even
     * on the array adapter). This exercises the real cache stack instead of the permissive
     * in-memory fake, so a reserved character creeping back into a key prefix fails loudly.
     */
    public function testWorksAgainstRealPsr16CacheWithoutReservedCharacters(): void
    {
        $repository = new RedisTeamInvitationRepository(new RedisCacheStore(new Psr16Cache(new ArrayAdapter())));
        $invitation = $this->makeInvitation(token: 'psr16-token', invitedUserId: 'user-5');

        $repository->save($invitation);

        $found = $repository->findByToken('psr16-token');
        $this->assertNotNull($found);
        $this->assertTrue($repository->hasPendingInvitation($this->teamId, 'user-5'));

        $repository->delete('psr16-token', $this->teamId, 'user-5');
        $this->assertNull($repository->findByToken('psr16-token'));
    }
}
