<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Persistence;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\Entity\SubscriptionTransition;
use App\SubscriptionFeature\Domain\Repository\SubscriptionRepositoryInterface;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findById(SubscriptionId $id): ?Subscription
    {
        return $this->entityManager->find(Subscription::class, $id->value());
    }

    public function findByUserAndSubject(string $userId, SubjectType $subjectType, string $subjectId): ?Subscription
    {
        return $this->entityManager->getRepository(Subscription::class)->findOneBy([
            'userId' => $userId,
            'subjectType' => $subjectType->value,
            'subjectId' => $subjectId,
        ]);
    }

    public function findByUserId(string $userId): array
    {
        return $this->entityManager->getRepository(Subscription::class)->findBy([
            'userId' => $userId,
        ]);
    }

    public function findBySubjectAndTransition(SubjectType $subjectType, string $subjectId, string $transitionId): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Subscription::class, 's')
            ->join(SubscriptionTransition::class, 'st', 'WITH', 'st.subscriptionId = s.id')
            ->where('s.subjectType = :subjectType')
            ->andWhere('s.subjectId = :subjectId')
            ->andWhere('st.workflowTransitionId = :transitionId')
            ->setParameter('subjectType', $subjectType->value)
            ->setParameter('subjectId', $subjectId)
            ->setParameter('transitionId', $transitionId)
            ->getQuery()
            ->getResult();
    }

    public function save(Subscription $subscription): void
    {
        $this->entityManager->persist($subscription);
    }

    public function delete(Subscription $subscription): void
    {
        $this->entityManager->remove($subscription);
    }
}
