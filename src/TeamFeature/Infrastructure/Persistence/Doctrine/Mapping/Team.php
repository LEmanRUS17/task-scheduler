<?php

declare(strict_types=1);

namespace App\TeamFeature\Infrastructure\Persistence\Doctrine\Mapping;

use App\TeamFeature\Domain\ValueObject\TeamStatus;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
if (!isset($metadata)) {
    return;
}

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('team');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('title', 'string', ['length' => 255]);
$builder->addField('status', 'string', ['enumType' => TeamStatus::class]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
