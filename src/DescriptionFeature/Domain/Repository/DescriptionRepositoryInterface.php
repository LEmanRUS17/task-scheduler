<?php

declare(strict_types=1);

namespace App\DescriptionFeature\Domain\Repository;

use App\DescriptionFeature\Domain\Entity\Description;

interface DescriptionRepositoryInterface
{
    public function findByEntity(string $entityClass, string $entityId): ?Description;

    public function save(Description $description): void;

    public function delete(Description $description): void;
}
