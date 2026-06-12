<?php

declare(strict_types=1);

namespace App\Tests\Integration\TeamFeature\Presentation\Controller;

use App\TeamFeatureApi\DTORequest\TeamUpdateRequestInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
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

final class UpdateTeamControllerTest extends WebTestCase
{
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('PATCH', '/team/' . self::TEAM_ID);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsNotFoundWhenTeamDoesNotExist(): void
    {
        $user = $this->makeUser('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('update')
            ->willThrowException(new \DomainException('Team ' . self::TEAM_ID . ' not found'));
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'PATCH',
            '/team/' . self::TEAM_ID,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['description' => 'New description']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdatesDescription(): void
    {
        $user = $this->makeUser('b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $team = $this->createStub(TeamDataResponseInterface::class);
        $team->method('getId')->willReturn(self::TEAM_ID);
        $team->method('getTitle')->willReturn('Backend');
        $team->method('getStatus')->willReturn('ACTIVE');
        $team->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $team->method('getDescription')->willReturn('New description');

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('update')
            ->with(
                self::TEAM_ID,
                $this->callback(
                    fn(TeamUpdateRequestInterface $request) => $request->getDescription() === 'New description',
                ),
            )
            ->willReturn($team);
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'PATCH',
            '/team/' . self::TEAM_ID,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['description' => 'New description']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::TEAM_ID, $body['id']);
        $this->assertSame('Backend', $body['title']);
        $this->assertSame('ACTIVE', $body['status']);
        $this->assertSame('2026-01-01T00:00:00+00:00', $body['createdAt']);
        $this->assertSame('New description', $body['description']);
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
