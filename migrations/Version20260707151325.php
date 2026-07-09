<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707151325 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create comments table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE comments (
                id VARCHAR(36) NOT NULL,
                entity_type VARCHAR(32) NOT NULL,
                entity_id VARCHAR(36) NOT NULL,
                author_id VARCHAR(36) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                edited_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY (id)
            )
        ');

        $this->addSql('
            CREATE INDEX idx_comment_entity ON comments (entity_type, entity_id, created_at)
        ');

        $this->addSql('
            CREATE INDEX idx_comment_author ON comments (author_id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE comments');
    }
}
