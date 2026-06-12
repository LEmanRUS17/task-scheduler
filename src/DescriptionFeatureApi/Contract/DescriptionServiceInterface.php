<?php

declare(strict_types=1);

namespace App\DescriptionFeatureApi\Contract;

interface DescriptionServiceInterface
{
    public function get(string $entityClass, string $entityId): ?string;

    public function set(string $entityClass, string $entityId, string $content): void;

    public function delete(string $entityClass, string $entityId): void;
}
