<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Repository;

use App\SubscriptionFeature\Domain\Entity\SubscriptionTransition;

interface SubscriptionTransitionRepositoryInterface
{
    /** @return list<SubscriptionTransition> */
    public function findBySubscriptionId(string $subscriptionId): array;

    /**
     * @param list<string> $subscriptionIds
     * @return array<string, list<SubscriptionTransition>>
     */
    public function findBySubscriptionIds(array $subscriptionIds): array;

    public function save(SubscriptionTransition $transition): void;

    public function deleteBySubscriptionId(string $subscriptionId): void;
}
