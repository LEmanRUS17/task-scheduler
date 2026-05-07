<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Persistence;

use App\SubscriptionFeature\Domain\Port\UnitOfWorkInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineUnitOfWork implements UnitOfWorkInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
