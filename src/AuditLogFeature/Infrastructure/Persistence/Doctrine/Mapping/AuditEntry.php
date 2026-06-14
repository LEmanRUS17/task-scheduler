<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\AuditLogFeature\Domain\Entity\AuditEntry> $metadata */

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('audit_log');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('entityClass', 'string', ['columnName' => 'entity_class', 'length' => 255]);
$builder->addField('entityId', 'string', ['columnName' => 'entity_id', 'length' => 255]);
$builder->addField('action', 'string', ['length' => 10]);
$builder->addField('changedData', 'json', ['columnName' => 'changed_data']);
$builder->addField('actorId', 'string', ['columnName' => 'actor_id', 'length' => 36, 'nullable' => true]);
$builder->addField('occurredAt', 'datetime_immutable', ['columnName' => 'occurred_at']);
