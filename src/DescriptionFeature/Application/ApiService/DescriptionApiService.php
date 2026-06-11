<?php

declare(strict_types=1);

namespace App\DescriptionFeature\Application\ApiService;

use App\DescriptionFeature\Domain\Entity\Description;
use App\DescriptionFeature\Domain\Repository\DescriptionRepositoryInterface;
use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;

final class DescriptionApiService implements DescriptionServiceInterface
{
    public function __construct(
        private readonly DescriptionRepositoryInterface $repository,
    ) {
    }

    public function get(string $entityClass, string $entityId): ?string
    {
        return $this->repository->findByEntity($entityClass, $entityId)?->content();
    }

    public function set(string $entityClass, string $entityId, string $content): void
    {
        $description = $this->repository->findByEntity($entityClass, $entityId);

        if ($description === null) {
            $description = Description::create(
                $this->generateUuid(),
                $entityClass,
                $entityId,
                $content,
                new \DateTimeImmutable(),
            );
        } else {
            $description->update($content, new \DateTimeImmutable());
        }

        $this->repository->save($description);
    }

    public function delete(string $entityClass, string $entityId): void
    {
        $description = $this->repository->findByEntity($entityClass, $entityId);

        if ($description !== null) {
            $this->repository->delete($description);
        }
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
