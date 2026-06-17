<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617152503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reference workflow statuses by id instead of label in workflow_transition';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_transition ADD from_status_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE workflow_transition ADD to_status_id VARCHAR(36) DEFAULT NULL');

        $this->addSql(
            'UPDATE workflow_transition t SET from_status_id = s.id '
                . 'FROM workflow_status s '
                . 'WHERE s.workflow_id = t.workflow_id AND s.label = t.from_status_label',
        );
        $this->addSql(
            'UPDATE workflow_transition t SET to_status_id = s.id '
                . 'FROM workflow_status s '
                . 'WHERE s.workflow_id = t.workflow_id AND s.label = t.to_status_label',
        );

        $this->addSql('ALTER TABLE workflow_transition ALTER COLUMN from_status_id SET NOT NULL');
        $this->addSql('ALTER TABLE workflow_transition ALTER COLUMN to_status_id SET NOT NULL');

        $this->addSql('ALTER TABLE workflow_transition DROP COLUMN from_status_label');
        $this->addSql('ALTER TABLE workflow_transition DROP COLUMN to_status_label');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_transition ADD from_status_label VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE workflow_transition ADD to_status_label VARCHAR(100) DEFAULT NULL');

        $this->addSql(
            'UPDATE workflow_transition t SET from_status_label = s.label '
                . 'FROM workflow_status s '
                . 'WHERE s.id = t.from_status_id',
        );
        $this->addSql(
            'UPDATE workflow_transition t SET to_status_label = s.label '
                . 'FROM workflow_status s '
                . 'WHERE s.id = t.to_status_id',
        );

        $this->addSql('ALTER TABLE workflow_transition ALTER COLUMN from_status_label SET NOT NULL');
        $this->addSql('ALTER TABLE workflow_transition ALTER COLUMN to_status_label SET NOT NULL');

        $this->addSql('ALTER TABLE workflow_transition DROP COLUMN from_status_id');
        $this->addSql('ALTER TABLE workflow_transition DROP COLUMN to_status_id');
    }
}
