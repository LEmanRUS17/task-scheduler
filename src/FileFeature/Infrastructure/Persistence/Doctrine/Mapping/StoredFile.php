<?php

declare(strict_types=1);

namespace App\FileFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\FileFeature\Domain\Entity\StoredFile> $metadata */

$metadata->table = [
    'name' => 'stored_files',
    'indexes' => [
        'idx_stored_file_entity' => ['columns' => ['entity_class', 'entity_id', 'purpose']],
    ],
];

$builder = new ClassMetadataBuilder($metadata);

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('entityClass', 'string', ['columnName' => 'entity_class', 'length' => 255]);
$builder->addField('entityId', 'string', ['columnName' => 'entity_id', 'length' => 36]);
$builder->addField('purpose', 'string', ['length' => 20]);
$builder->addField('originalName', 'string', ['columnName' => 'name', 'length' => 255]);
$builder->addField('storagePath', 'string', ['columnName' => 'path', 'length' => 512]);
$builder->addField('mimeType', 'string', ['columnName' => 'type', 'length' => 255]);
$builder->addField('size', 'integer');
$builder->addField('uploadedBy', 'string', ['columnName' => 'uploaded_by', 'length' => 36]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
