<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\TaskFeature\Domain\Entity\TaskAssignee> $metadata */

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('task_assignee');

$metadata->setIdentifier(['taskId', 'userId']);

$builder->createField('taskId', 'string')
    ->columnName('task_id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->createField('userId', 'string')
    ->columnName('user_id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('assignedAt', 'datetime_immutable', ['columnName' => 'assigned_at']);
