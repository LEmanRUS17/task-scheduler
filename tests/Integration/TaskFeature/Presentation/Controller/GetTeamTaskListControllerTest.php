<?php

declare(strict_types=1);

namespace App\Tests\Integration\TaskFeature\Presentation\Controller;

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

final class GetTeamTaskListControllerTest extends WebTestCase
{
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/team/' . self::TEAM_ID . '/task');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsForbiddenWhenUserIsNotTeamMember(): void
    {
        $userId = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';
        $user = $this->makeUser($userId);

        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TaskServiceInterface::class);
        $service->expects($this->once())
            ->method('getListByTeam')
            ->with(self::TEAM_ID, $userId)
            ->willThrowException(new \DomainException('Access denied.'));
        static::getContainer()->set(TaskServiceInterface::class, $service);

        $client->request('GET', '/team/' . self::TEAM_ID . '/task', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testReturnsEmptyListWhenTeamHasNoTasks(): void
    {
        $userId = 'b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e';
        $user = $this->makeUser($userId);

        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TaskServiceInterface::class);
        $service->expects($this->once())
            ->method('getListByTeam')
            ->with(self::TEAM_ID, $userId)
            ->willReturn([]);
        static::getContainer()->set(TaskServiceInterface::class, $service);

        $client->request('GET', '/team/' . self::TEAM_ID . '/task', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $body['tasks']);
    }

    public function testReturnsTasksForTeam(): void
    {
        $userId = 'd4e5f6a7-b8c9-4d0e-af2a-3b4c5d6e7f8a';
        $user = $this->makeUser($userId);

        $client = static::createClient();
        $this->stubUserRepository($user);

        $createdAt = new \DateTimeImmutable('2026-01-01T10:00:00+00:00');
        $task = $this->createStub(TaskDataResponseInterface::class);
        $task->method('getId')->willReturn('e5f6a7b8-c9d0-4e1f-2a3b-4c5d6e7f8a9b');
        $task->method('getTitle')->willReturn('Team Task');
        $task->method('getStatus')->willReturn('open');
        $task->method('getPriority')->willReturn('normal');
        $task->method('getTeamId')->willReturn(self::TEAM_ID);
        $task->method('getCreatedBy')->willReturn('d4e5f6a7-b8c9-4d0e-af2a-3b4c5d6e7f8a');
        $task->method('getAssigneeIds')->willReturn([]);
        $task->method('getScheduledStart')->willReturn(null);
        $task->method('getScheduledEnd')->willReturn(null);
        $task->method('getEstimatedTime')->willReturn(null);
        $task->method('getActualTime')->willReturn(null);
        $task->method('getCreatedAt')->willReturn($createdAt);
        $task->method('getAvailableTransitions')->willReturn([]);

        $service = $this->createMock(TaskServiceInterface::class);
        $service->expects($this->once())
            ->method('getListByTeam')
            ->with(self::TEAM_ID, $userId)
            ->willReturn([$task]);
        static::getContainer()->set(TaskServiceInterface::class, $service);

        $client->request('GET', '/team/' . self::TEAM_ID . '/task', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['tasks']);
        $this->assertSame('e5f6a7b8-c9d0-4e1f-2a3b-4c5d6e7f8a9b', $body['tasks'][0]['id']);
        $this->assertSame('Team Task', $body['tasks'][0]['title']);
        $this->assertSame(self::TEAM_ID, $body['tasks'][0]['teamId']);
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
