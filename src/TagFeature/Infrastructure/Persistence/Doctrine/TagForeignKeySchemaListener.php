<?php

declare(strict_types=1);

namespace App\TagFeature\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

final class TagForeignKeySchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (!$schema->hasTable('tag_assignments') || !$schema->hasTable('tags')) {
            return;
        }

        $schema->getTable('tag_assignments')->addForeignKeyConstraint(
            'tags',
            ['tag_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_tag_assignment_tag',
        );
    }
}
