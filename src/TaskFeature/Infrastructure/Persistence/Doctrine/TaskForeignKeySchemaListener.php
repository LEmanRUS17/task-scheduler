<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

final class TaskForeignKeySchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (!$schema->hasTable('task_assignee') || !$schema->hasTable('task')) {
            return;
        }

        $schema->getTable('task_assignee')->addForeignKeyConstraint(
            'task',
            ['task_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_task_assignee_task',
        );
    }
}
