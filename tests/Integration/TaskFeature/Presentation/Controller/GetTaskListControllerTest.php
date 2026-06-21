<?php

declare(strict_types=1);

namespace App\Tests\Integration\TaskFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetTaskListControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/task');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testWithoutQueryReturnsPaginatedListWithDefaults(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->expects($this->once())
            ->method('getPage')
            ->with(self::USER_ID, 10, 0)
            ->willReturn([$this->makeTask('task-1', 'Fix bug')]);
        $taskService->method('countAll')->willReturn(1);
        $taskService->expects($this->never())->method('getByIds');
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->never())->method('searchTasks');
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $client->request('GET', '/task', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['tasks']);
        $this->assertSame('task-1', $body['tasks'][0]['id']);
        $this->assertSame('Fix bug', $body['tasks'][0]['title']);
        $this->assertSame(['page' => 1, 'limit' => 10, 'pages' => 1], $body['pagination']);
        $this->assertSame(1, $body['count']);
    }

    public function testCountIsReturnedEvenWhenPageIsEmpty(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getPage')->willReturn([]);
        $taskService->method('countAll')->willReturn(0);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request('GET', '/task', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $body['tasks']);
        $this->assertSame(0, $body['count']);
        $this->assertSame(0, $body['pagination']['pages']);
    }

    /**
     * @return array<string, array{0: int|null, 1: int}>
     */
    public static function limitProvider(): array
    {
        return [
            'default when omitted' => [null, 10],
            'explicit 10' => [10, 10],
            'explicit 20' => [20, 20],
            'explicit 50' => [50, 50],
            'invalid falls back to default' => [999, 10],
            'zero falls back to default' => [0, 10],
        ];
    }

    #[DataProvider('limitProvider')]
    public function testLimitIsValidatedAgainstAllowedValues(?int $requested, int $expected): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->expects($this->once())
            ->method('getPage')
            ->with(self::USER_ID, $expected, 0)
            ->willReturn([]);
        $taskService->method('countAll')->willReturn(0);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $url = '/task' . ($requested !== null ? '?limit=' . $requested : '');
        $client->request('GET', $url, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($expected, $body['pagination']['limit']);
    }

    public function testPageComputesOffsetAndPages(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->expects($this->once())
            ->method('getPage')
            ->with(self::USER_ID, 20, 40) // page 3, limit 20 => offset 40
            ->willReturn([]);
        $taskService->method('countAll')->willReturn(45);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request('GET', '/task?page=3&limit=20', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $body['pagination']['page']);
        $this->assertSame(20, $body['pagination']['limit']);
        $this->assertSame(3, $body['pagination']['pages']); // ceil(45 / 20)
        $this->assertSame(45, $body['count']);
    }

    public function testShortQueryFallsBackToList(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->expects($this->once())->method('getPage')->willReturn([]);
        $taskService->method('countAll')->willReturn(0);
        $taskService->expects($this->never())->method('getByIds');
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->never())->method('searchTasks');
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $client->request('GET', '/task?q=a', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testWithQuerySearchesPaginatedThenHydratesPreservingOrder(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->once())
            ->method('searchTasks')
            ->with('fix', self::USER_ID, null, null, 10, 0)
            ->willReturn(['ids' => ['task-2', 'task-1'], 'total' => 2]);
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->expects($this->never())->method('getPage');
        $taskService->expects($this->once())
            ->method('getByIds')
            ->with(['task-2', 'task-1'])
            ->willReturn([
                $this->makeTask('task-2', 'Add tests'),
                $this->makeTask('task-1', 'Fix bug'),
            ]);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request('GET', '/task?q=fix', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['task-2', 'task-1'], array_column($body['tasks'], 'id'));
        $this->assertSame(2, $body['count']);
    }

    public function testSearchForwardsTeamIdAndStatusFiltersAndUsesTotalForCount(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->once())
            ->method('searchTasks')
            ->with('fix', self::USER_ID, 'team-1', 'open', 20, 20)
            ->willReturn(['ids' => [], 'total' => 37]);
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getByIds')->willReturn([]);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request('GET', '/task?q=fix&team_id=team-1&status=open&page=2&limit=20', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(37, $body['count']);
        $this->assertSame(2, $body['pagination']['pages']); // ceil(37 / 20)
    }

    private function makeTask(string $id, string $title): TaskDataResponseInterface
    {
        $task = $this->createStub(TaskDataResponseInterface::class);
        $task->method('getId')->willReturn($id);
        $task->method('getTitle')->willReturn($title);
        $task->method('getStatus')->willReturn('open');
        $task->method('getStatusId')->willReturn('status-1');
        $task->method('getPriority')->willReturn('normal');
        $task->method('getTeamId')->willReturn('team-1');
        $task->method('getCreatedBy')->willReturn(self::USER_ID);
        $task->method('getAssigneeIds')->willReturn([]);
        $task->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-01-01 00:00:00'));
        $task->method('getScheduledStart')->willReturn(null);
        $task->method('getScheduledEnd')->willReturn(null);
        $task->method('getEstimatedTime')->willReturn(null);
        $task->method('getActualTime')->willReturn(null);
        $task->method('getAvailableTransitions')->willReturn([]);
        $task->method('getDescription')->willReturn(null);

        return $task;
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
