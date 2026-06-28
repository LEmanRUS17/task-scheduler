<?php

declare(strict_types=1);

namespace App\TagFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\TagFeature\Domain\Entity\TagAssignment> $metadata */

$metadata->table = [
    'name' => 'tag_assignments',
    'uniqueConstraints' => [
        ['name' => 'uq_tag_assignment', 'columns' => ['tag_id', 'entity_type', 'entity_id']],
    ],
    'indexes' => [
        ['name' => 'idx_tag_assignment_entity', 'columns' => ['entity_type', 'entity_id']],
        ['name' => 'idx_tag_assignment_tag', 'columns' => ['tag_id']],
    ],
];

$builder = new ClassMetadataBuilder($metadata);

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('tagId', 'string', ['columnName' => 'tag_id', 'length' => 36]);
$builder->addField('entityType', 'string', ['columnName' => 'entity_type', 'length' => 32]);
$builder->addField('entityId', 'string', ['columnName' => 'entity_id', 'length' => 36]);
$builder->addField('assignedBy', 'string', ['columnName' => 'assigned_by', 'length' => 36]);
$builder->addField('assignedAt', 'datetime_immutable', ['columnName' => 'assigned_at']);
