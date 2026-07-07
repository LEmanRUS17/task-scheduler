<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707153138 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add parent_id to comments for replies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE comments ADD parent_id VARCHAR(36) DEFAULT NULL');

        $this->addSql('
            CREATE INDEX idx_comment_parent ON comments (parent_id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_comment_parent');
        $this->addSql('ALTER TABLE comments DROP COLUMN parent_id');
    }
}
