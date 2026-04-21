<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\WorkflowFeature\Domain\Entity\WorkflowTransition> $metadata */

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('workflow_transition');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('workflowId', 'string', ['columnName' => 'workflow_id', 'length' => 36]);
$builder->addField('name', 'string', ['length' => 100]);
$builder->addField('fromStatusLabel', 'string', ['columnName' => 'from_status_label', 'length' => 100]);
$builder->addField('toStatusLabel', 'string', ['columnName' => 'to_status_label', 'length' => 100]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
