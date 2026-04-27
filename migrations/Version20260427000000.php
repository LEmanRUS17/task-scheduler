<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create task_assignee table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE task_assignee (
                task_id   VARCHAR(36) NOT NULL,
                user_id   VARCHAR(36) NOT NULL,
                assigned_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (task_id, user_id)
            )
        ');

        $this->addSql('
            INSERT INTO task_assignee (task_id, user_id, assigned_at)
            SELECT id, created_by, created_at FROM task
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE task_assignee');
    }
}
