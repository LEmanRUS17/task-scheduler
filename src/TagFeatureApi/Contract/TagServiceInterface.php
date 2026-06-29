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
}
