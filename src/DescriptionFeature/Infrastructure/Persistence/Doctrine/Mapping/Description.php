<?php

declare(strict_types=1);

namespace App\DescriptionFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\DescriptionFeature\Domain\Entity\Description> $metadata */

$metadata->table = [
    'name' => 'descriptions',
    'uniqueConstraints' => [
        ['name' => 'uq_description_entity', 'columns' => ['entity_class', 'entity_id']],
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
$builder->addField('content', 'text');
$builder->addField('updatedAt', 'datetime_immutable', ['columnName' => 'updated_at']);
