<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

final class SubscriptionForeignKeySchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (!$schema->hasTable('subscription')) {
            return;
        }

        if ($schema->hasTable('subscription_channel')) {
            $schema->getTable('subscription_channel')->addForeignKeyConstraint(
                'subscription',
                ['subscription_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
                'fk_subscription_channel_subscription',
            );
        }

        if ($schema->hasTable('subscription_transition')) {
            $schema->getTable('subscription_transition')->addForeignKeyConstraint(
                'subscription',
                ['subscription_id'],
                ['id'],
                ['onDelete' => 'CASCADE'],
                'fk_subscription_transition_subscription',
            );
        }
    }
}
