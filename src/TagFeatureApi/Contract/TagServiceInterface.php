<?php

declare(strict_types=1);

namespace App\TagFeatureApi\Contract;

use App\TagFeatureApi\DTOResponse\TagResponseInterface;

interface TagServiceInterface
{
    public const TYPE_TASK = 'task';
    public const TYPE_TEAM = 'team';
    public const TYPE_WORKFLOW = 'workflow';

    /**
     * Returns the tag with the given id, or null when it does not exist.
     */
    public function getById(string $id): ?TagResponseInterface;

    /**
     * Returns every tag, regardless of owner.
     *
     * @return TagResponseInterface[]
     */
    public function getList(): array;

    /**
     * Returns the names of the tags assigned to the given entity.
     *
     * @return list<string>
     */
    public function getEntityTagNames(string $entityType, string $entityId): array;

    /**
     * Returns the tags assigned to each of the given entities, grouped by entity id.
     *
     * Entities without tags are omitted from the result.
     *
     * @param list<string> $entityIds
     * @return array<string, list<TagResponseInterface>>
     */
    public function getEntityTagsByIds(string $entityType, array $entityIds): array;

    /**
     * Assigns the given tag to the given entity. The operation is idempotent.
     */
    public function assign(string $tagId, string $entityType, string $entityId, string $assignedBy): void;

    /**
     * Returns the subset of the given tag ids that correspond to an existing tag.
     *
     * @param list<string> $tagIds
     * @return list<string>
     */
    public function filterExistingTagIds(array $tagIds): array;

    /**
     * Returns the tags assigned to the entity.
     *
     * Entities without tags are excluded from the result.
     *
     * @return array
     */
    public function getEntityTagsById(string $entityType, string $entityId): array;
}
