<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Interactor;

use App\SubscriptionFeature\Domain\Exception\SubscriptionAccessDeniedException;
use App\SubscriptionFeature\Domain\Exception\SubscriptionNotFoundException;
use App\SubscriptionFeature\Domain\Port\DomainEventDispatcherInterface;
use App\SubscriptionFeature\Domain\Port\UnitOfWorkInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionTransitionRepositoryInterface;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;

final class UnsubscribeInteractor
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly SubscriptionTransitionRepositoryInterface $transitions,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {
    }

    public function unsubscribe(SubscriptionId $id, string $userId): void
    {
        $subscription = $this->subscriptions->findById($id);

        if ($subscription === null) {
            throw new SubscriptionNotFoundException($id->value());
        }

        if ($subscription->userId() !== $userId) {
            throw new SubscriptionAccessDeniedException();
        }

        $subscription->delete();

        $this->transitions->deleteBySubscriptionId($subscription->id()->value());
        $this->subscriptions->delete($subscription);
        $this->unitOfWork->flush();

        $this->eventDispatcher->dispatch(...$subscription->pullDomainEvents());
    }
}
