<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260622154602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create stored_files table for FileFeature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE stored_files ('
                . 'id VARCHAR(36) NOT NULL, '
                . 'entity_class VARCHAR(255) NOT NULL, '
                . 'entity_id VARCHAR(36) NOT NULL, '
                . 'purpose VARCHAR(20) NOT NULL, '
                . 'name VARCHAR(255) NOT NULL, '
                . 'path VARCHAR(512) NOT NULL, '
                . 'type VARCHAR(255) NOT NULL, '
                . 'size INT NOT NULL, '
                . 'uploaded_by VARCHAR(36) NOT NULL, '
                . 'created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, '
                . 'PRIMARY KEY (id))'
        );
        $this->addSql(
            'CREATE INDEX idx_stored_file_entity ON stored_files (entity_class, entity_id, purpose)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE stored_files');
    }
}
