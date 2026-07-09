<?php

declare(strict_types=1);

namespace App\ProfileFeature\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

final class ProfileForeignKeySchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (!$schema->hasTable('profile') || !$schema->hasTable('user')) {
            return;
        }

        $schema->getTable('profile')->addForeignKeyConstraint(
            'user',
            ['user_id'],
            ['id'],
            [],
            'fk_8157aa0fa76ed395',
        );
    }
}
