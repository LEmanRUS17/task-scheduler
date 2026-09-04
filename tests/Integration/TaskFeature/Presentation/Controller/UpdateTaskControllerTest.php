<?php

declare(strict_types=1);

namespace App\Tests\Integration\TaskFeature\Presentation\Controller;

use App\TaskFeatureApi\DTORequest\TaskUpdateRequestInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class UpdateTaskControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';
    private const TASK_ID = 'b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e';

    public function testDescriptionSentOverHttpReachesTheTaskService(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $existingTask = $this->makeTask(self::USER_ID, 'Old description');
        $updatedTask = $this->makeTask(self::USER_ID, 'New description');

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->method('getById')->with(self::TASK_ID)->willReturn($existingTask);
        $taskService->expects($this->once())
            ->method('update')
            ->with(
                self::TASK_ID,
                self::callback(
                    static fn (TaskUpdateRequestInterface $request): bool =>
                        $request->getDescription() === 'New description',
                ),
            )
            ->willReturn($updatedTask);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request(
            'PATCH',
            '/task/' . self::TASK_ID,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['description' => 'New description'], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('New description', $body['description']);
    }

    public function testUpdatingOtherFieldsDoesNotClearDescription(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $existingTask = $this->makeTask(self::USER_ID, 'Kept description');

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->method('getById')->with(self::TASK_ID)->willReturn($existingTask);
        $taskService->expects($this->once())
            ->method('update')
            ->with(
                self::TASK_ID,
                self::callback(
                    static fn (TaskUpdateRequestInterface $request): bool => $request->getDescription() === null
                        && $request->getTitle() === 'New title',
                ),
            )
            ->willReturn($existingTask);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request(
            'PATCH',
            '/task/' . self::TASK_ID,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'New title'], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Kept description', $body['description']);
    }

    public function testAssigneeIdsSentOverHttpReachTheTaskService(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $existingTask = $this->makeTask(self::USER_ID, 'Description');

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->method('getById')->with(self::TASK_ID)->willReturn($existingTask);
        $taskService->expects($this->once())
            ->method('update')
            ->with(
                self::TASK_ID,
                self::callback(
                    static fn (TaskUpdateRequestInterface $request): bool =>
                        $request->getAssigneeIds() === ['user-a', 'user-b'],
                ),
            )
            ->willReturn($existingTask);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request(
            'PATCH',
            '/task/' . self::TASK_ID,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['assigneeIds' => ['user-a', 'user-b']], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();
    }

    public function testUpdatingOtherFieldsDoesNotTouchAssignees(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $existingTask = $this->makeTask(self::USER_ID, 'Description');

        $taskService = $this->createMock(TaskServiceInterface::class);
        $taskService->method('getById')->with(self::TASK_ID)->willReturn($existingTask);
        $taskService->expects($this->once())
            ->method('update')
            ->with(
                self::TASK_ID,
                self::callback(
                    static fn (TaskUpdateRequestInterface $request): bool => $request->getAssigneeIds() === null,
                ),
            )
            ->willReturn($existingTask);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request(
            'PATCH',
            '/task/' . self::TASK_ID,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'New title'], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseIsSuccessful();
    }

    private function makeTask(string $createdBy, ?string $description): TaskDataResponseInterface
    {
        $task = $this->createStub(TaskDataResponseInterface::class);
        $task->method('getId')->willReturn(self::TASK_ID);
        $task->method('getTitle')->willReturn('Task title');
        $task->method('getStatus')->willReturn('open');
        $task->method('getStatusId')->willReturn('status-1');
        $task->method('getPriority')->willReturn('normal');
        $task->method('getTeamId')->willReturn(null);
        $task->method('getWorkflowId')->willReturn('default');
        $task->method('getCreatedBy')->willReturn($createdBy);
        $task->method('getAssigneeIds')->willReturn([]);
        $task->method('getAssigneeProfiles')->willReturn([]);
        $task->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-01-01 00:00:00'));
        $task->method('getScheduledStart')->willReturn(null);
        $task->method('getScheduledEnd')->willReturn(null);
        $task->method('getEstimatedTime')->willReturn(null);
        $task->method('getActualTime')->willReturn(null);
        $task->method('getAvailableTransitions')->willReturn([]);
        $task->method('getDescription')->willReturn($description);
        $task->method('isClosed')->willReturn(false);
        $task->method('getClosedAt')->willReturn(null);
        $task->method('getState')->willReturn('planned');

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
