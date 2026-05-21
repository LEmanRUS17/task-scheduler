<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Persistence;

use App\SubscriptionFeature\Domain\Entity\SubscriptionChannel;
use App\SubscriptionFeature\Domain\Repository\SubscriptionChannelRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSubscriptionChannelRepository implements SubscriptionChannelRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findBySubscriptionId(string $subscriptionId): array
    {
        return $this->entityManager->getRepository(SubscriptionChannel::class)->findBy([
            'subscriptionId' => $subscriptionId,
        ]);
    }

    public function findBySubscriptionIds(array $subscriptionIds): array
    {
        if (empty($subscriptionIds)) {
            return [];
        }

        $channels = $this->entityManager->getRepository(SubscriptionChannel::class)->findBy([
            'subscriptionId' => $subscriptionIds,
        ]);

        $grouped = [];
        foreach ($channels as $channel) {
            $grouped[$channel->subscriptionId()][] = $channel;
        }

        return $grouped;
    }

    public function save(SubscriptionChannel $channel): void
    {
        $this->entityManager->persist($channel);
    }

    public function deleteBySubscriptionId(string $subscriptionId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(SubscriptionChannel::class, 'sc')
            ->where('sc.subscriptionId = :subscriptionId')
            ->setParameter('subscriptionId', $subscriptionId)
            ->getQuery()
            ->execute();
    }
}
