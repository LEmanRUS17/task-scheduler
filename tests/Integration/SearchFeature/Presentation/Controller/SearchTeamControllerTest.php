<?php

declare(strict_types=1);

namespace App\Tests\Integration\SearchFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\SearchFeatureApi\DTOResponse\TeamSearchResultInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SearchTeamControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/teams/search?q=backend');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsBadRequestWhenQueryIsTooShort(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $client->request('GET', '/teams/search?q=b', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    public function testReturnsResultsScopedByMembership(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $result = $this->makeResult('team-1', 'Backend', 'active');

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTeams')
            ->with('backend', self::USER_ID, [], false)
            ->willReturn([$result]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/teams/search?q=backend', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['results']);
        $this->assertSame('team-1', $body['results'][0]['teamId']);
        $this->assertSame('Backend', $body['results'][0]['title']);
        $this->assertSame('active', $body['results'][0]['status']);
    }

    public function testReturnsResultsFilteredBySingleStatus(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTeams')
            ->with('backend', self::USER_ID, ['active'], false)
            ->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/teams/search?q=backend&status=active', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testReturnsResultsFilteredByMultipleArrayStatuses(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTeams')
            ->with('backend', self::USER_ID, ['active', 'archived'], false)
            ->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/teams/search?q=backend&status[]=active&status[]=archived', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testReturnsResultsFilteredByCommaSeparatedStatuses(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTeams')
            ->with('backend', self::USER_ID, ['active', 'archived'], false)
            ->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/teams/search?q=backend&status=active,archived', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testReturnsOnlyOwnedResultsWhenOwnerRequested(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTeams')
            ->with('backend', self::USER_ID, [], true)
            ->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/teams/search?q=backend&owner=true', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testReturnsAllResultsWhenOwnerIsFalse(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTeams')
            ->with('backend', self::USER_ID, [], false)
            ->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/teams/search?q=backend&owner=false', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testReturnsEmptyResultsWhenNothingFound(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createStub(SearchServiceInterface::class);
        $service->method('searchTeams')->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/teams/search?q=nothing', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $body['results']);
    }

    private function makeResult(string $teamId, string $title, string $status): TeamSearchResultInterface
    {
        $result = $this->createStub(TeamSearchResultInterface::class);
        $result->method('getTeamId')->willReturn($teamId);
        $result->method('getTitle')->willReturn($title);
        $result->method('getStatus')->willReturn($status);

        return $result;
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
