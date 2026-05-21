<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Persistence\Doctrine\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/** @var \Doctrine\ORM\Mapping\ClassMetadata<\App\SubscriptionFeature\Domain\Entity\SubscriptionTransition> $metadata */
// @phpstan-ignore-next-line isset.variable
if (!isset($metadata)) {
    return;
}

$builder = new ClassMetadataBuilder($metadata);
$builder->setTable('subscription_transition');

$builder->createField('subscriptionId', 'string')
    ->columnName('subscription_id')
    ->length(36)
    ->makePrimaryKey()
    ->build();

$builder->createField('workflowTransitionId', 'string')
    ->columnName('workflow_transition_id')
    ->length(36)
    ->makePrimaryKey()
    ->build();
