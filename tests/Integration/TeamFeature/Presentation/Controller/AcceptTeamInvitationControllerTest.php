<?php

declare(strict_types=1);

namespace App\Tests\Integration\TeamFeature\Presentation\Controller;

use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface;
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

final class AcceptTeamInvitationControllerTest extends WebTestCase
{
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/team/invitation/accept');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAcceptsInvitationAndReturnsMembership(): void
    {
        $user = $this->makeUser('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $joinedAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $member = $this->createStub(TeamMemberDataResponseInterface::class);
        $member->method('getTeamId')->willReturn(self::TEAM_ID);
        $member->method('getUserId')->willReturn($user->id()->value());
        $member->method('getRole')->willReturn('member');
        $member->method('getJoinedAt')->willReturn($joinedAt);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('acceptInvitation')
            ->with(
                $this->callback(fn($request) => $request->getToken() === 'token-abc'),
                $user->id()->value(),
            )
            ->willReturn($member);
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'POST',
            '/team/invitation/accept',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['token' => 'token-abc']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::TEAM_ID, $body['teamId']);
        $this->assertSame($user->id()->value(), $body['userId']);
        $this->assertSame('member', $body['role']);
    }

    public function testReturnsConflictWhenTokenInvalid(): void
    {
        $user = $this->makeUser('b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('acceptInvitation')
            ->willThrowException(new \DomainException('Invitation not found'));
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'POST',
            '/team/invitation/accept',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['token' => 'unknown-token']),
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
