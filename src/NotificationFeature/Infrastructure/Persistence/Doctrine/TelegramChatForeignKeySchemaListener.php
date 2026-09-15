<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;

final class TelegramChatForeignKeySchemaListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (!$schema->hasTable('telegram_chat') || !$schema->hasTable('user')) {
            return;
        }

        $schema->getTable('telegram_chat')->addForeignKeyConstraint(
            'user',
            ['user_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_telegram_chat_user',
        );
    }
}
