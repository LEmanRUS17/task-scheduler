<?php

declare(strict_types=1);

namespace App\Tests\Integration\TeamFeature\Presentation\Controller;

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

final class DeleteTeamControllerTest extends WebTestCase
{
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/team/' . self::TEAM_ID);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsNotFoundWhenTeamDoesNotExist(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('deleteByIdForUser')
            ->with(self::TEAM_ID, self::USER_ID)
            ->willThrowException(new \DomainException('Team ' . self::TEAM_ID . ' not found'));
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request('DELETE', '/team/' . self::TEAM_ID, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeletesTeamAndReturnsNoContent(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('deleteByIdForUser')
            ->with(self::TEAM_ID, self::USER_ID);
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request('DELETE', '/team/' . self::TEAM_ID, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $this->assertSame('', $client->getResponse()->getContent());
    }

    private function makeUser(): User
    {
        return User::register(
            UserId::fromString(self::USER_ID),
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
