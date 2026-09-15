<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260915165944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add state column to telegram_chat';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telegram_chat ADD state INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE telegram_chat DROP state');
    }
}
