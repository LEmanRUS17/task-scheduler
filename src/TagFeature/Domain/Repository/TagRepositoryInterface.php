<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Repository;

use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\ValueObject\TagId;

interface TagRepositoryInterface
{
    public function save(Tag $tag): void;

    public function delete(Tag $tag): void;

    public function findById(TagId $id): ?Tag;

    public function findByOwnerAndName(string $ownerId, string $name): ?Tag;

    /** @return Tag[] */
    public function findByOwnerPaginated(string $ownerId, int $limit, int $offset): array;

    public function countByOwner(string $ownerId): int;

    /**
     * @param list<string> $ids
     * @return Tag[]
     */
    public function findByIds(array $ids): array;
}
