<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Repository;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;

interface SubscriptionRepositoryInterface
{
    public function findById(SubscriptionId $id): ?Subscription;

    public function findByUserAndSubject(string $userId, SubjectType $subjectType, string $subjectId): ?Subscription;

    /** @return list<Subscription> */
    public function findByUserId(string $userId): array;

    public function save(Subscription $subscription): void;

    public function delete(Subscription $subscription): void;
}
