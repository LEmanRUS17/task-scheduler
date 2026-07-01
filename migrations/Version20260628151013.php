<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260628151013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tags and tag_assignments tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE tags (
                id VARCHAR(36) NOT NULL,
                owner_id VARCHAR(36) NOT NULL,
                name VARCHAR(64) NOT NULL,
                color VARCHAR(7) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ');

        $this->addSql('
            CREATE UNIQUE INDEX uq_tag_owner_name ON tags (owner_id, name)
        ');

        $this->addSql('
            CREATE TABLE tag_assignments (
                id VARCHAR(36) NOT NULL,
                tag_id VARCHAR(36) NOT NULL,
                entity_type VARCHAR(32) NOT NULL,
                entity_id VARCHAR(36) NOT NULL,
                assigned_by VARCHAR(36) NOT NULL,
                assigned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ');

        $this->addSql('
            CREATE UNIQUE INDEX uq_tag_assignment ON tag_assignments (tag_id, entity_type, entity_id)
        ');

        $this->addSql('
            CREATE INDEX idx_tag_assignment_entity ON tag_assignments (entity_type, entity_id)
        ');

        $this->addSql('
            CREATE INDEX idx_tag_assignment_tag ON tag_assignments (tag_id)
        ');

        $this->addSql('
            ALTER TABLE tag_assignments
            ADD CONSTRAINT fk_tag_assignment_tag
            FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE tag_assignments');
        $this->addSql('DROP TABLE tags');
    }
}
