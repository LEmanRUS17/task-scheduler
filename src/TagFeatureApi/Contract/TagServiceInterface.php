<?php

declare(strict_types=1);

namespace App\TagFeatureApi\Contract;

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
}
