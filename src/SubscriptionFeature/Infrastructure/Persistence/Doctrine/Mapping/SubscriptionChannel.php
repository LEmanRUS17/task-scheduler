<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
if (!isset($metadata)) {
    return;
}

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('subscription_channel');

$builder->createField('subscriptionId', 'string')
    ->columnName('subscription_id')
    ->length(36)
    ->makePrimaryKey()
    ->build();

$builder->createField('channel', 'string')
    ->columnName('channel')
    ->length(30)
    ->makePrimaryKey()
    ->build();
