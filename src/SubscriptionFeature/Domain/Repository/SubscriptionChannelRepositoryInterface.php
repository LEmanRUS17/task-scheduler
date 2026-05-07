<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Repository;

use App\SubscriptionFeature\Domain\Entity\SubscriptionChannel;

interface SubscriptionChannelRepositoryInterface
{
    /** @return list<SubscriptionChannel> */
    public function findBySubscriptionId(string $subscriptionId): array;

    /**
     * @param list<string> $subscriptionIds
     * @return array<string, list<SubscriptionChannel>>
     */
    public function findBySubscriptionIds(array $subscriptionIds): array;

    public function save(SubscriptionChannel $channel): void;

    public function deleteBySubscriptionId(string $subscriptionId): void;
}
