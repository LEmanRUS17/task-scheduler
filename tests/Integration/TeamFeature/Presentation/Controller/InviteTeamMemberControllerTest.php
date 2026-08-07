<?php

declare(strict_types=1);

namespace App\Tests\Integration\TeamFeature\Presentation\Controller;

use App\TeamFeatureApi\DTOResponse\TeamInvitationDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class InviteTeamMemberControllerTest extends WebTestCase
{
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/team/' . self::TEAM_ID . '/invitation');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreatesInvitation(): void
    {
        $user = $this->makeUser('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $expiresAt = new \DateTimeImmutable('2026-01-08T00:00:00+00:00');

        $invitation = $this->createStub(TeamInvitationDataResponseInterface::class);
        $invitation->method('getId')->willReturn('invitation-1');
        $invitation->method('getTeamId')->willReturn(self::TEAM_ID);
        $invitation->method('getInvitedUserId')->willReturn('invitee-uuid');
        $invitation->method('getInvitedByUserId')->willReturn($user->id()->value());
        $invitation->method('getRole')->willReturn('member');
        $invitation->method('getStatus')->willReturn('pending');
        $invitation->method('getCreatedAt')->willReturn($createdAt);
        $invitation->method('getExpiresAt')->willReturn($expiresAt);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('inviteMember')
            ->with(
                self::TEAM_ID,
                $this->callback(fn($request) => $request->getEmail() === 'invitee@example.com'),
                $user->id()->value(),
            )
            ->willReturn($invitation);
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'POST',
            '/team/' . self::TEAM_ID . '/invitation',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['email' => 'invitee@example.com', 'role' => 'member']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('invitation-1', $body['id']);
        $this->assertSame(self::TEAM_ID, $body['teamId']);
        $this->assertSame('invitee-uuid', $body['invitedUserId']);
        $this->assertSame('pending', $body['status']);
    }

    public function testReturnsConflictWhenUserAlreadyMember(): void
    {
        $user = $this->makeUser('b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('inviteMember')
            ->willThrowException(new \DomainException('User is already a member of team ' . self::TEAM_ID));
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'POST',
            '/team/' . self::TEAM_ID . '/invitation',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['email' => 'invitee@example.com', 'role' => 'member']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
    }

    private function makeUser(string $userId): User
    {
        return User::register(
            UserId::fromString($userId),
            Email::fromString('test@example.com'),
            HashedPassword::fromHash('$2y$04$dummyhashfortestingpurposesonly123456'),
            new \DateTimeImmutable(),
        );
    }

    private function stubUserRepository(User $user): void
    {
        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn($user);
        static::getContainer()->set(UserRepositoryInterface::class, $repo);
    }

    private function generateToken(User $user): string
    {
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        return $jwtManager->createFromPayload(new SecurityUser($user), [
            'sub' => $user->email()->value(),
        ]);
    }
}
