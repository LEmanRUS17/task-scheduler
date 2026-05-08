<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Interactor;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\Entity\SubscriptionChannel;
use App\SubscriptionFeature\Domain\Entity\SubscriptionTransition;
use App\SubscriptionFeature\Domain\Exception\SubscriptionAccessDeniedException;
use App\SubscriptionFeature\Domain\Exception\SubscriptionNotFoundException;
use App\SubscriptionFeature\Domain\Port\DomainEventDispatcherInterface;
use App\SubscriptionFeature\Domain\Port\UnitOfWorkInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionChannelRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionTransitionRepositoryInterface;
use App\SubscriptionFeatureApi\ValueObject\NotificationChannel;
use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;

final class UpdateSubscriptionInteractor
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly SubscriptionChannelRepositoryInterface $channels,
        private readonly SubscriptionTransitionRepositoryInterface $transitions,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {}

    /** @param list<string> $transitionIds */
    public function update(
        SubscriptionId $id,
        string $userId,
        NotificationChannelMask $channelMask,
        array $transitionIds,
    ): Subscription {
        $subscription = $this->subscriptions->findById($id);

        if ($subscription === null) {
            throw new SubscriptionNotFoundException($id->value());
        }

        if ($subscription->userId() !== $userId) {
            throw new SubscriptionAccessDeniedException();
        }

        $subscription->updateChannels($channelMask);
        $this->subscriptions->save($subscription);

        $this->channels->deleteBySubscriptionId($subscription->id()->value());
        foreach (NotificationChannel::cases() as $channel) {
            if ($channelMask->has($channel)) {
                $this->channels->save(SubscriptionChannel::create($subscription->id()->value(), $channel));
            }
        }

        $this->transitions->deleteBySubscriptionId($subscription->id()->value());
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
