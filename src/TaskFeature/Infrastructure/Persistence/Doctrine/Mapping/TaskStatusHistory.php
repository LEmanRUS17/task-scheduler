<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\TaskFeature\Domain\Entity\TaskStatusHistory> $metadata */

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('task_status_history');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addIndex(['task_id'], 'idx_task_status_history_task_id');

$builder->addField('taskId', 'string', ['columnName' => 'task_id', 'length' => 36]);
$builder->addField('transitionId', 'string', ['columnName' => 'transition_id', 'length' => 36]);
$builder->addField('changedBy', 'string', ['columnName' => 'changed_by', 'length' => 36, 'nullable' => true]);
$builder->addField('changedAt', 'datetime_immutable', ['columnName' => 'changed_at']);
