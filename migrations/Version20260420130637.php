<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420130637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workflow, workflow_status, workflow_transition tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workflow (id VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, created_by VARCHAR(36) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE workflow_status (id VARCHAR(36) NOT NULL, workflow_id VARCHAR(36) NOT NULL, label VARCHAR(100) NOT NULL, is_initial BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE workflow_transition (id VARCHAR(36) NOT NULL, workflow_id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, from_status_label VARCHAR(100) NOT NULL, to_status_label VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workflow');
        $this->addSql('DROP TABLE workflow_status');
        $this->addSql('DROP TABLE workflow_transition');
    }
}
