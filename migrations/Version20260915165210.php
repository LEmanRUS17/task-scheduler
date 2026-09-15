<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260915165210 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add telegram_chat table linking a user to their Telegram chat id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE telegram_chat (user_id VARCHAR(36) NOT NULL, chat_id BIGINT NOT NULL, PRIMARY KEY (user_id))');
        $this->addSql('ALTER TABLE telegram_chat ADD CONSTRAINT fk_telegram_chat_user FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telegram_chat DROP CONSTRAINT fk_telegram_chat_user');
        $this->addSql('DROP TABLE telegram_chat');
    }
}
