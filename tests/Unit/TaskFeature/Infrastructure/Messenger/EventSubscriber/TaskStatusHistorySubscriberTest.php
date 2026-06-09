<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Infrastructure\Messenger\EventSubscriber;

use App\TaskFeature\Domain\Entity\TaskStatusHistory;
use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Domain\Repository\TaskStatusHistoryRepositoryInterface;
use App\TaskFeature\Infrastructure\Messenger\EventSubscriber\TaskStatusHistorySubscriber;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class TaskStatusHistorySubscriberTest extends TestCase
{
    private function makeEvent(
        string $taskId = 'task-uuid',
        string $transitionId = 'transition-uuid',
    ): TaskStatusChanged {
        return new TaskStatusChanged($taskId, $transitionId, 'todo', 'in_progress', 'default', null);
    }

    private function captureRepository(?TaskStatusHistory &$captured = null): TaskStatusHistoryRepositoryInterface
    {
        $repository = $this->createMock(TaskStatusHistoryRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (TaskStatusHistory $entry) use (&$captured): void {
                $captured = $entry;
            });

        return $repository;
    }

    private function noTokenSecurity(): Security
    {
        $security = $this->createStub(Security::class);
        $security->method('getToken')->willReturn(null);

        return $security;
    }

    private function securityWithUser(UserInterface $user): Security
    {
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $security = $this->createStub(Security::class);
        $security->method('getToken')->willReturn($token);

        return $security;
    }

    private function makeSecurityUser(string $userId = 'user-uuid'): SecurityUser
    {
        return new SecurityUser(User::register(
            UserId::fromString($userId),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hashed'),
            new \DateTimeImmutable(),
        ));
    }

    // --- save ---

    public function testSavesEntryOnEvent(): void
    {
        $captured = null;
        $subscriber = new TaskStatusHistorySubscriber(
            $this->captureRepository($captured),
            $this->noTokenSecurity(),
        );

        $subscriber->onTaskStatusChanged($this->makeEvent());

        $this->assertInstanceOf(TaskStatusHistory::class, $captured);
    }

    public function testSavedEntryHasCorrectTaskId(): void
    {
        $captured = null;
        $subscriber = new TaskStatusHistorySubscriber(
            $this->captureRepository($captured),
            $this->noTokenSecurity(),
        );

        $subscriber->onTaskStatusChanged($this->makeEvent(taskId: 'task-123'));

        $this->assertSame('task-123', $captured->taskId());
    }

    public function testSavedEntryHasCorrectTransitionId(): void
    {
        $captured = null;
        $subscriber = new TaskStatusHistorySubscriber(
            $this->captureRepository($captured),
            $this->noTokenSecurity(),
        );

        $subscriber->onTaskStatusChanged($this->makeEvent(transitionId: 'tr-456'));

        $this->assertSame('tr-456', $captured->transitionId());
    }

    public function testSavedEntryHasChangedAt(): void
    {
        $before = new \DateTimeImmutable();

        $captured = null;
        $subscriber = new TaskStatusHistorySubscriber(
            $this->captureRepository($captured),
            $this->noTokenSecurity(),
        );

        $subscriber->onTaskStatusChanged($this->makeEvent());

        $this->assertGreaterThanOrEqual($before, $captured->changedAt());
    }

    // --- changedBy ---

    public function testChangedByIsNullWhenNoToken(): void
    {
        $captured = null;
        $subscriber = new TaskStatusHistorySubscriber(
            $this->captureRepository($captured),
            $this->noTokenSecurity(),
        );

        $subscriber->onTaskStatusChanged($this->makeEvent());

        $this->assertNull($captured->changedBy());
    }

    public function testChangedByIsNullWhenUserIsNotSecurityUser(): void
    {
        $captured = null;
        $subscriber = new TaskStatusHistorySubscriber(
            $this->captureRepository($captured),
            $this->securityWithUser($this->createStub(UserInterface::class)),
        );

        $subscriber->onTaskStatusChanged($this->makeEvent());

        $this->assertNull($captured->changedBy());
    }

    public function testChangedByIsResolvedFromSecurityUser(): void
    {
        $userId = 'a1b2c3d4-0000-4000-8000-000000000001';

        $captured = null;
        $subscriber = new TaskStatusHistorySubscriber(
            $this->captureRepository($captured),
            $this->securityWithUser($this->makeSecurityUser($userId)),
        );

        $subscriber->onTaskStatusChanged($this->makeEvent());

        $this->assertSame($userId, $captured->changedBy());
    }
}
