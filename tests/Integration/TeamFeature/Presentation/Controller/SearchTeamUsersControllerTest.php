<?php

declare(strict_types=1);

namespace App\Tests\Integration\TeamFeature\Presentation\Controller;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
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

final class SearchTeamUsersControllerTest extends WebTestCase
{
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/team/' . self::TEAM_ID . '/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSearchesUsersByFormFieldName(): void
    {
        $user = $this->makeUser('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d');
        $client = static::createClient();
        $this->stubUserRepository($user);

        $member = $this->createStub(TeamMemberDataResponseInterface::class);
        $member->method('getUserId')->willReturn('ivan-uuid');
        $member->method('getProfile')->willReturn($this->makeProfile());

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('searchMembers')
            ->with(self::TEAM_ID, $user->id()->value(), 'ivan')
            ->willReturn([$member]);
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'POST',
            '/team/' . self::TEAM_ID . '/users',
            parameters: ['name' => 'ivan'],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user)],
        );

        $this->assertResponseIsSuccessful();

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['users']);
        $this->assertSame('ivan-uuid', $body['users'][0]['userId']);
        $this->assertSame('ivan_doe', $body['users'][0]['user']['username']);
    }

    public function testWithoutNameFieldSearchesWithNullName(): void
    {
        $user = $this->makeUser('b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e');
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('searchMembers')
            ->with(self::TEAM_ID, $user->id()->value(), null)
            ->willReturn([]);
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'POST',
            '/team/' . self::TEAM_ID . '/users',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user)],
        );

        $this->assertResponseIsSuccessful();
        $this->assertSame(['users' => []], json_decode($client->getResponse()->getContent(), true));
    }

    public function testReturnsNotFoundWhenTeamDoesNotExist(): void
    {
        $user = $this->makeUser('c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7a');
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->method('searchMembers')->willThrowException(new \DomainException('Team not found'));
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request(
            'POST',
            '/team/' . self::TEAM_ID . '/users',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user)],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function makeProfile(): ProfileDataResponseInterface
    {
        $profile = $this->createStub(ProfileDataResponseInterface::class);
        $profile->method('getUserId')->willReturn('ivan-uuid');
        $profile->method('getUsername')->willReturn('ivan_doe');
        $profile->method('getFirstname')->willReturn('Ivan');
        $profile->method('getLastname')->willReturn('Ivanov');
        $profile->method('getMidlname')->willReturn(null);
        $profile->method('getAvatar')->willReturn(null);

        return $profile;
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
