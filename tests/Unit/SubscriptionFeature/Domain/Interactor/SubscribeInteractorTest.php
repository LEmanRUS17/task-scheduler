<?php

declare(strict_types=1);

namespace App\Tests\Unit\SubscriptionFeature\Domain\Interactor;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\Interactor\SubscribeInteractor;
use App\SubscriptionFeature\Domain\Port\ClockInterface;
use App\SubscriptionFeature\Domain\Port\DomainEventDispatcherInterface;
use App\SubscriptionFeature\Domain\Port\UnitOfWorkInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionChannelRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionTransitionRepositoryInterface;
use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;
use PHPUnit\Framework\TestCase;

final class SubscribeInteractorTest extends TestCase
{
    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));
    }

    private function buildInteractor(
        SubscriptionRepositoryInterface $subscriptions,
        SubscriptionChannelRepositoryInterface $channels,
        SubscriptionTransitionRepositoryInterface $transitions,
        ?DomainEventDispatcherInterface $dispatcher = null,
        ?UnitOfWorkInterface $unitOfWork = null,
    ): SubscribeInteractor {
        return new SubscribeInteractor(
            $subscriptions,
            $channels,
            $transitions,
            $dispatcher ?? $this->createStub(DomainEventDispatcherInterface::class),
            $this->clock,
            $unitOfWork ?? $this->createStub(UnitOfWorkInterface::class),
        );
    }

    public function testSubscribeReturnsSubscription(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);

        $result = $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
        )->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::none(), []);

        $this->assertInstanceOf(Subscription::class, $result);
        $this->assertSame('user-1', $result->userId());
        $this->assertSame(SubjectType::TASK, $result->subjectType());
        $this->assertSame('task-uuid', $result->subjectId());
    }

    public function testSubscribeSavesSubscription(): void
    {
        $subscriptions = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);
        $subscriptions->expects($this->once())->method('save');

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
        )->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::none(), []);
    }

    public function testSubscribeSavesChannelForEachEnabledChannel(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);

        $channels = $this->createMock(SubscriptionChannelRepositoryInterface::class);
        $channels->expects($this->once())->method('save');

        $this->buildInteractor($subscriptions, $channels, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::fromInt(NotificationChannel::EMAIL->value), []);
    }

    public function testSubscribeSavesBothChannelsWhenBothEnabled(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);

        $channels = $this->createMock(SubscriptionChannelRepositoryInterface::class);
        $channels->expects($this->exactly(2))->method('save');

        $this->buildInteractor($subscriptions, $channels, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::fromInt(NotificationChannelMask::MAX), []);
    }

    public function testSubscribeDoesNotSaveChannelsWhenMaskIsEmpty(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);

        $channels = $this->createMock(SubscriptionChannelRepositoryInterface::class);
        $channels->expects($this->never())->method('save');

        $this->buildInteractor($subscriptions, $channels, $this->createStub(SubscriptionTransitionRepositoryInterface::class))
            ->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::none(), []);
    }

    public function testSubscribeSavesTransitions(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);

        $transitions = $this->createMock(SubscriptionTransitionRepositoryInterface::class);
        $transitions->expects($this->exactly(2))->method('save');

        $this->buildInteractor($subscriptions, $this->createStub(SubscriptionChannelRepositoryInterface::class), $transitions)
            ->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::none(), ['tr-1', 'tr-2']);
    }

    public function testSubscribeWithNoTransitionsDoesNotSaveTransitions(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);

        $transitions = $this->createMock(SubscriptionTransitionRepositoryInterface::class);
        $transitions->expects($this->never())->method('save');

        $this->buildInteractor($subscriptions, $this->createStub(SubscriptionChannelRepositoryInterface::class), $transitions)
            ->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::none(), []);
    }

    public function testSubscribeFlushesUnitOfWork(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);

        $unitOfWork = $this->createMock(UnitOfWorkInterface::class);
        $unitOfWork->expects($this->once())->method('flush');

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
            unitOfWork: $unitOfWork,
        )->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::none(), []);
    }

    public function testSubscribeDispatchesEvent(): void
    {
        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn(null);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch');

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
            $dispatcher,
        )->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::none(), []);
    }

    public function testSubscribeThrowsWhenAlreadySubscribed(): void
    {
        $existing = Subscription::create(
            \App\SubscriptionFeature\Domain\ValueObject\SubscriptionId::generate(),
            'user-1',
            SubjectType::TASK,
            'task-uuid',
            new \DateTimeImmutable(),
        );

        $subscriptions = $this->createStub(SubscriptionRepositoryInterface::class);
        $subscriptions->method('findByUserAndSubject')->willReturn($existing);

        $this->expectException(\DomainException::class);

        $this->buildInteractor(
            $subscriptions,
            $this->createStub(SubscriptionChannelRepositoryInterface::class),
            $this->createStub(SubscriptionTransitionRepositoryInterface::class),
        )->subscribe('user-1', SubjectType::TASK, 'task-uuid', NotificationChannelMask::none(), []);
    }
}
