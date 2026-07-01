<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Repository;

use App\TagFeature\Domain\Entity\TagAssignment;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TaggableType;

interface TagAssignmentRepositoryInterface
{
    public function save(TagAssignment $assignment): void;

    public function delete(TagAssignment $assignment): void;

    public function find(TagId $tagId, TaggableType $entityType, string $entityId): ?TagAssignment;

    /** @return TagAssignment[] */
    public function findByEntity(TaggableType $entityType, string $entityId): array;

    /** @return TagAssignment[] */
    public function findByTag(TagId $tagId): array;

    /**
     * Returns the distinct ids of tags assigned to any of the given entities.
     *
     * @param list<string> $entityIds
     * @return list<string>
     */
    public function findTagIdsByEntityIds(TaggableType $entityType, array $entityIds): array;

    /**
     * Returns the ids of tags assigned to each of the given entities, grouped by entity id.
     *
     * @param list<string> $entityIds
     * @return array<string, list<string>>
     */
    public function findTagIdsByEntityIdsGrouped(TaggableType $entityType, array $entityIds): array;

    /**
     * Returns the ids of entities of the given type that carry the given tag.
     *
     * @return list<string>
     */
    public function findEntityIdsByTag(TaggableType $entityType, TagId $tagId): array;
}
