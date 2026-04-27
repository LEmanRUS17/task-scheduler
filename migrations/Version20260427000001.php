<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'task.id ON DELETE CASCADE';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            ALTER TABLE task_assignee
            ADD CONSTRAINT fk_task_assignee_task
            FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task_assignee DROP CONSTRAINT fk_task_assignee_task');
    }
}
