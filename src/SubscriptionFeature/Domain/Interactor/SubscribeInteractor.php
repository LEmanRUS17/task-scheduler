<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Interactor;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\Entity\SubscriptionChannel;
use App\SubscriptionFeature\Domain\Entity\SubscriptionTransition;
use App\SubscriptionFeature\Domain\Port\ClockInterface;
use App\SubscriptionFeature\Domain\Port\DomainEventDispatcherInterface;
use App\SubscriptionFeature\Domain\Port\UnitOfWorkInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionChannelRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionTransitionRepositoryInterface;
use App\SubscriptionFeature\Domain\ValueObject\NotificationChannel;
use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;

final class SubscribeInteractor
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly SubscriptionChannelRepositoryInterface $channels,
        private readonly SubscriptionTransitionRepositoryInterface $transitions,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {}

    /** @param list<string> $transitionIds */
    public function subscribe(
        string $userId,
        SubjectType $subjectType,
        string $subjectId,
        NotificationChannelMask $channelMask,
        array $transitionIds,
    ): Subscription {
        if ($this->subscriptions->findByUserAndSubject($userId, $subjectType, $subjectId) !== null) {
            throw new \DomainException("User {$userId} is already subscribed to {$subjectType->value} {$subjectId}");
        }

        $subscription = Subscription::create(
            SubscriptionId::generate(),
            $userId,
            $subjectType,
            $subjectId,
            $this->clock->now(),
        );

        $this->subscriptions->save($subscription);

        foreach (NotificationChannel::cases() as $channel) {
            if ($channelMask->has($channel)) {
                $this->channels->save(SubscriptionChannel::create($subscription->id()->value(), $channel));
            }
        }

        foreach ($transitionIds as $transitionId) {
            $this->transitions->save(
                SubscriptionTransition::create($subscription->id()->value(), $transitionId),
            );
        }

        $this->unitOfWork->flush();
        $this->eventDispatcher->dispatch(...$subscription->pullDomainEvents());

        return $subscription;
    }
}
