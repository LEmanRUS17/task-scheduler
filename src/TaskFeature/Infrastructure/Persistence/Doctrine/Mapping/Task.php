<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Persistence\Doctrine\Mapping;

use App\TaskFeature\Domain\ValueObject\TaskPriority;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\TaskFeature\Domain\Entity\Task> $metadata */

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('task');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('title', 'string', ['length' => 255]);
$builder->addField('priority', 'string', ['enumType' => TaskPriority::class]);
$builder->addField('workflowStatus', 'string', ['columnName' => 'workflow_status', 'length' => 100]);
$builder->addField('workflowDefinitionTitle', 'string', ['columnName' => 'workflow_definition_title', 'length' => 255]);
$builder->addField('createdBy', 'string', ['columnName' => 'created_by', 'length' => 36]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
$builder->addField('scheduledStart', 'datetime_immutable', ['columnName' => 'scheduled_start', 'nullable' => true]);
$builder->addField('scheduledEnd', 'datetime_immutable', ['columnName' => 'scheduled_end', 'nullable' => true]);
$builder->addField('estimatedTime', 'integer', ['columnName' => 'estimated_time', 'nullable' => true]);
$builder->addField('actualTime', 'integer', ['columnName' => 'actual_time', 'nullable' => true]);
