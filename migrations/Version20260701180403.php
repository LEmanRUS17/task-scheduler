<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701180403 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_final flag to workflow_status and closed_at to task';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_status ADD is_final BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE task ADD closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow_status DROP COLUMN is_final');
        $this->addSql('ALTER TABLE task DROP COLUMN closed_at');
    }
}
