<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260609165900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_status_history table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE task_status_history (
            id VARCHAR(36) NOT NULL,
            task_id VARCHAR(36) NOT NULL,
            transition_id VARCHAR(36) NOT NULL,
            changed_by VARCHAR(36) DEFAULT NULL,
            changed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY (id)
        )');
        $this->addSql('CREATE INDEX IDX_TASK_STATUS_HISTORY_TASK_ID ON task_status_history (task_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_status_history');
    }
}
