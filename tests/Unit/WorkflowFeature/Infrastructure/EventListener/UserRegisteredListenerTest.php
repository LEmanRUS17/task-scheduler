<?php

declare(strict_types=1);

namespace App\Tests\Unit\WorkflowFeature\Infrastructure\EventListener;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Event\UserRegistered;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\WorkflowFeature\Infrastructure\EventListener\UserRegisteredListener;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use PHPUnit\Framework\TestCase;

final class UserRegisteredListenerTest extends TestCase
{
    public function testInvokeCallsCreateDefaultForUserWithUserId(): void
    {
        $userId = UserId::generate();
        $user = User::register(
            $userId,
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('hash'),
            new \DateTimeImmutable(),
        );

        /** @var UserRegistered $event */
        $event = $user->pullDomainEvents()[0];

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())
            ->method('createDefaultForUser')
            ->with($userId->value())
            ->willReturn($this->createStub(WorkflowResponseInterface::class));

        (new UserRegisteredListener($workflowService))($event);
    }
}
