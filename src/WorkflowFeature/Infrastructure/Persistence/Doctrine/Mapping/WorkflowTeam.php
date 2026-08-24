<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\WorkflowFeature\Domain\Entity\WorkflowTeam> $metadata */

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('workflow_team');

$metadata->setIdentifier(['teamId', 'workflowId']);

$builder->createField('teamId', 'string')
    ->columnName('team_id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->createField('workflowId', 'string')
    ->columnName('workflow_id')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('attachedAt', 'datetime_immutable', ['columnName' => 'attached_at']);
