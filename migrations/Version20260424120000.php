<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task (
            id VARCHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            priority VARCHAR(20) NOT NULL,
            workflow_status VARCHAR(100) NOT NULL DEFAULT \'\',
            workflow_definition_title VARCHAR(255) NOT NULL,
            created_by VARCHAR(36) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            scheduled_start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            scheduled_end TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            estimated_time INT DEFAULT NULL,
            actual_time INT DEFAULT NULL,
            PRIMARY KEY (id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task');
    }
}
