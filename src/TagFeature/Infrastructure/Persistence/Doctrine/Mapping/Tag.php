<?php

declare(strict_types=1);

namespace App\TagFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\TagFeature\Domain\Entity\Tag> $metadata */

$metadata->table = [
    'name' => 'tags',
    'uniqueConstraints' => [
        ['name' => 'uq_tag_owner_name', 'columns' => ['owner_id', 'name']],
    ],
];

$builder = new ClassMetadataBuilder($metadata);

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('ownerId', 'string', ['columnName' => 'owner_id', 'length' => 36]);
$builder->addField('name', 'string', ['length' => 64]);
$builder->addField('color', 'string', ['length' => 7]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
