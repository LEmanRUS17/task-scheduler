<?php

declare(strict_types=1);

namespace App\Tests\Unit\SubscriptionFeature\Domain\Interactor;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\Exception\SubscriptionAccessDeniedException;
use App\SubscriptionFeature\Domain\Exception\SubscriptionNotFoundException;
use App\SubscriptionFeature\Domain\Interactor\UnsubscribeInteractor;
use App\SubscriptionFeature\Domain\Port\DomainEventDispatcherInterface;
use App\SubscriptionFeature\Domain\Port\UnitOfWorkInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionTransitionRepositoryInterface;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;
use PHPUnit\Framework\TestCase;

final class UnsubscribeInteractorTest extends TestCase
{
    private SubscriptionId $subscriptionId;
    private Subscription $subscription;

    protected function setUp(): void
    {
        $this->subscriptionId = SubscriptionId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $this->subscription = Subscription::create(
            $this->subscriptionId,
            'user-1',
            SubjectType::TASK,
            'task-uuid',
            new \DateTimeImmutable(),
        );
    }

    private function buildInteractor(
        SubscriptionRepositoryInterface $subscriptions,
        SubscriptionTransitionRepositoryInterface $transitions,
        ?DomainEventDispatcherInterface $dispatcher = null,
        ?UnitOfWorkInterface $unitOfWork = null,
    ): UnsubscribeInteractor {
        return new UnsubscribeInteractor(
            $subscriptions,
            $transitions,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $unitOfWork ?? $this->createStub(UnitOfWorkInterface::class),
        );
    }

    public function testUnsubscribeDeletesTransitions(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $transitions = $this->createMock(SubscriptionTransitionRepositoryInterface::class);
        $transitions->expects($this->once())
            ->method('deleteBySubscriptionId')
            ->with($this->subscriptionId->value());

        $this->buildInteractor($subscriptions, $transitions)->unsubscribe($this->subscriptionId, 'user-1');
    }

    public function testUnsubscribeDeletesSubscription(): void
    {
        $subscriptions = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);
        $subscriptions->expects($this->once())->method('delete')->with($this->subscription);

        $this->buildInteractor($subscriptions, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->unsubscribe($this->subscriptionId, 'user-1');
    }

    public function testUnsubscribeFlushesUnitOfWork(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $unitOfWork = $this->createMock(UnitOfWorkInterface::class);
        $unitOfWork->expects($this->once())->method('flush');

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
            unitOfWork: $unitOfWork,
        )->unsubscribe($this->subscriptionId, 'user-1');
    }

    public function testUnsubscribeDispatchesEvent(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
            $dispatcher,
        )->unsubscribe($this->subscriptionId, 'user-1');
    }

    public function testUnsubscribeThrowsWhenNotFound(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn(null);

        $this->expectException(SubscriptionNotFoundException::class);

        $this->buildInteractor($subscriptions, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->unsubscribe($this->subscriptionId, 'user-1');
    }

    public function testUnsubscribeThrowsWhenAccessDenied(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $this->expectException(SubscriptionAccessDeniedException::class);

        $this->buildInteractor($subscriptions, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->unsubscribe($this->subscriptionId, 'other-user');
    }
}
