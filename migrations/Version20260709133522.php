<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709133522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deleted_at to comments for soft delete';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comments ADD deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comments DROP COLUMN deleted_at');
    }
}
