<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
if (!isset($metadata)) {
    return;
}

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('subscription');

$builder->createField('id', 'string')
    ->length(36)
    ->makePrimaryKey()
    ->generatedValue('NONE')
    ->build();

$builder->addField('userId', 'string', ['columnName' => 'user_id', 'length' => 36]);
$builder->addField('subjectType', 'string', ['columnName' => 'subject_type', 'length' => 50]);
$builder->addField('subjectId', 'string', ['columnName' => 'subject_id', 'length' => 36]);
$builder->addField('createdAt', 'datetime_immutable', ['columnName' => 'created_at']);
