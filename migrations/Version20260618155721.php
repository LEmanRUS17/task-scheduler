<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618155721 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store workflow status id instead of label in task.workflow_status';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'UPDATE task t SET workflow_status = s.id '
                . 'FROM workflow_status s '
                . 'WHERE s.workflow_id = t.workflow_definition_title AND s.label = t.workflow_status',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE task t SET workflow_status = s.label '
                . 'FROM workflow_status s '
                . 'WHERE s.workflow_id = t.workflow_definition_title AND s.id = t.workflow_status',
        );
    }
}
