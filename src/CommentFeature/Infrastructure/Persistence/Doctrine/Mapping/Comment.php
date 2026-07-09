<?php

declare(strict_types=1);

namespace App\CommentFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\CommentFeature\Domain\Entity\Comment> $metadata */

$metadata->table = [
    'name' => 'comments',
    'indexes' => [
        'idx_comment_entity' => ['columns' => ['entity_type', 'entity_id', 'created_at']],
        'idx_comment_author' => ['columns' => ['author_id']],
        'idx_comment_parent' => ['columns' => ['parent_id']],
    ],
];

$builder = new ClassMetadataBuilder($metadata);

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('entityType', 'string', ['columnName' => 'entity_type', 'length' => 32]);
$builder->addField('entityId', 'string', ['columnName' => 'entity_id', 'length' => 36]);
$builder->addField('authorId', 'string', ['columnName' => 'author_id', 'length' => 36]);
$builder->addField('content', 'text');
$builder->addField('parentId', 'string', ['columnName' => 'parent_id', 'length' => 36, 'nullable' => true]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
$builder->addField('editedAt', 'datetime_immutable', ['columnName' => 'edited_at', 'nullable' => true]);
$builder->addField('deletedAt', 'datetime_immutable', ['columnName' => 'deleted_at', 'nullable' => true]);
