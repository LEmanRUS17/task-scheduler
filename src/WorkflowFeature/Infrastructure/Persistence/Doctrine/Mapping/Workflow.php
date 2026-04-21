<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\WorkflowFeature\Domain\Entity\Workflow> $metadata */

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('workflow');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('title', 'string', ['length' => 255]);
$builder->addField('createdBy', 'string', ['columnName' => 'created_by', 'length' => 36]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
