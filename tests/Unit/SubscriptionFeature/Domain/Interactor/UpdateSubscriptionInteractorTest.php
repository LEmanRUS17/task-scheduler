<?php

declare(strict_types=1);

namespace App\Tests\Unit\SubscriptionFeature\Domain\Interactor;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\Exception\SubscriptionAccessDeniedException;
use App\SubscriptionFeature\Domain\Exception\SubscriptionNotFoundException;
use App\SubscriptionFeature\Domain\Interactor\UpdateSubscriptionInteractor;
use App\SubscriptionFeature\Domain\Port\DomainEventDispatcherInterface;
use App\SubscriptionFeature\Domain\Port\UnitOfWorkInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionChannelRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionTransitionRepositoryInterface;
use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;
use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;
use PHPUnit\Framework\TestCase;

final class UpdateSubscriptionInteractorTest extends TestCase
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
        SubscriptionChannelRepositoryInterface $channels,
        SubscriptionTransitionRepositoryInterface $transitions,
        ?DomainEventDispatcherInterface $dispatcher = null,
        ?UnitOfWorkInterface $unitOfWork = null,
    ): UpdateSubscriptionInteractor {
        return new UpdateSubscriptionInteractor(
            $subscriptions,
            $channels,
            $transitions,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $unitOfWork ?? $this->createStub(UnitOfWorkInterface::class),
        );
    }

    public function testUpdateReturnsSubscription(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $result = $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
        )->update($this->subscriptionId, 'user-1', NotificationChannelMask::none(), []);

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertSame($this->subscriptionId->value(), $result->id()->value());
    }

    public function testUpdateSavesSubscription(): void
    {
        $subscriptions = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);
        $subscriptions->expects($this->once())->method('save');

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
        )->update($this->subscriptionId, 'user-1', NotificationChannelMask::none(), []);
    }

    public function testUpdateDeletesOldChannels(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $channels = $this->createMock(SubscriptionChannelRepositoryInterface::class);
        $channels->expects($this->once())
            ->method('deleteBySubscriptionId')
            ->with($this->subscriptionId->value());

        $this->buildInteractor($subscriptions, $channels, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->update($this->subscriptionId, 'user-1', NotificationChannelMask::none(), []);
    }

    public function testUpdateSavesNewChannels(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $channels = $this->createMock(SubscriptionChannelRepositoryInterface::class);
        $channels->expects($this->once())->method('save');

        $this->buildInteractor($subscriptions, $channels, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->update($this->subscriptionId, 'user-1', NotificationChannelMask::fromInt(NotificationChannel::EMAIL->value), []);
    }

    public function testUpdateSavesBothChannelsWhenBothEnabled(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $channels = $this->createMock(SubscriptionChannelRepositoryInterface::class);
        $channels->expects($this->exactly(2))->method('save');

        $this->buildInteractor($subscriptions, $channels, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->update($this->subscriptionId, 'user-1', NotificationChannelMask::fromInt(NotificationChannelMask::MAX), []);
    }

    public function testUpdateDeletesOldTransitions(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $transitions = $this->createMock(SubscriptionTransitionRepositoryInterface::class);
        $transitions->expects($this->once())
            ->method('deleteBySubscriptionId')
            ->with($this->subscriptionId->value());

        $this->buildInteractor($subscriptions, $this->createStub(SubscriptionChannelRepositoryInterface::class), $transitions)
            ->update($this->subscriptionId, 'user-1', NotificationChannelMask::none(), []);
    }

    public function testUpdateSavesNewTransitions(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $transitions = $this->createMock(SubscriptionTransitionRepositoryInterface::class);
        $transitions->expects($this->exactly(2))->method('save');

        $this->buildInteractor($subscriptions, $this->createStub(SubscriptionChannelRepositoryInterface::class), $transitions)
            ->update($this->subscriptionId, 'user-1', NotificationChannelMask::none(), ['tr-1', 'tr-2']);
    }

    public function testUpdateFlushesUnitOfWork(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $unitOfWork = $this->createMock(UnitOfWorkInterface::class);
        $unitOfWork->expects($this->once())->method('flush');

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
            unitOfWork: $unitOfWork,
        )->update($this->subscriptionId, 'user-1', NotificationChannelMask::none(), []);
    }

    public function testUpdateDispatchesEvent(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
            $dispatcher,
        )->update($this->subscriptionId, 'user-1', NotificationChannelMask::none(), []);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn(null);

        $this->expectException(SubscriptionNotFoundException::class);

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
        )->update($this->subscriptionId, 'user-1', NotificationChannelMask::none(), []);
    }

    public function testUpdateThrowsWhenAccessDenied(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findById')->willReturn($this->subscription);

        $this->expectException(SubscriptionAccessDeniedException::class);

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
        )->update($this->subscriptionId, 'other-user', NotificationChannelMask::none(), []);
    }
}
