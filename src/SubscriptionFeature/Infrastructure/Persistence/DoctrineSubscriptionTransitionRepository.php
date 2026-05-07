<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Persistence;

use App\SubscriptionFeature\Domain\Entity\SubscriptionTransition;
use App\SubscriptionFeature\Domain\Repository\SubscriptionTransitionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSubscriptionTransitionRepository implements SubscriptionTransitionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findBySubscriptionId(string $subscriptionId): array
    {
        return $this->entityManager->getRepository(SubscriptionTransition::class)->findBy([
            'subscriptionId' => $subscriptionId,
        ]);
    }

    public function findBySubscriptionIds(array $subscriptionIds): array
    {
        if (empty($subscriptionIds)) {
            return [];
        }

        $transitions = $this->entityManager->getRepository(SubscriptionTransition::class)->findBy([
            'subscriptionId' => $subscriptionIds,
        ]);

        $grouped = [];
        foreach ($transitions as $transition) {
            $grouped[$transition->subscriptionId()][] = $transition;
        }

        return $grouped;
    }

    public function save(SubscriptionTransition $transition): void
    {
        $this->entityManager->persist($transition);
    }

    public function deleteBySubscriptionId(string $subscriptionId): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete(SubscriptionTransition::class, 'st')
            ->where('st.subscriptionId = :subscriptionId')
            ->setParameter('subscriptionId', $subscriptionId)
            ->getQuery()
            ->execute();
    }
}
