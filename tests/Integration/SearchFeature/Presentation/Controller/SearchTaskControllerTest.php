<?php

declare(strict_types=1);

namespace App\Tests\Integration\SearchFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\SearchFeatureApi\DTOResponse\TaskSearchResultInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SearchTaskControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/tasks/search?q=fix');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsBadRequestWhenQueryIsTooShort(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $client->request('GET', '/tasks/search?q=f', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    public function testReturnsResultsFilteredByUserId(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $result = $this->makeResult('task-1', 'Fix bug', 'open');

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTasks')
            ->with('fix', self::USER_ID, null, null)
            ->willReturn([$result]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/tasks/search?q=fix', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['results']);
        $this->assertSame('task-1', $body['results'][0]['taskId']);
        $this->assertSame('Fix bug', $body['results'][0]['title']);
        $this->assertSame('open', $body['results'][0]['status']);
    }

    public function testReturnsResultsFilteredByTeamId(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $result = $this->makeResult('task-2', 'Team task', 'done');

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTasks')
            ->with('team', self::USER_ID, self::TEAM_ID, null)
            ->willReturn([$result]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/tasks/search?q=team&team_id=' . self::TEAM_ID, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['results']);
        $this->assertSame('task-2', $body['results'][0]['taskId']);
    }

    public function testReturnsResultsFilteredByStatus(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchTasks')
            ->with('fix', self::USER_ID, null, 'open')
            ->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/tasks/search?q=fix&status=open', server: [
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
        $service->method('searchTasks')->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/tasks/search?q=nothing', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $body['results']);
    }

    private function makeResult(string $taskId, string $title, string $status): TaskSearchResultInterface
    {
        $result = $this->createStub(TaskSearchResultInterface::class);
        $result->method('getTaskId')->willReturn($taskId);
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
