<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\WorkflowFeature\Domain\Entity\WorkflowStatus> $metadata */

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('workflow_status');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('workflowId', 'string', ['columnName' => 'workflow_id', 'length' => 36]);
$builder->addField('label', 'string', ['length' => 100]);
$builder->addField('isInitial', 'boolean', ['columnName' => 'is_initial']);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
