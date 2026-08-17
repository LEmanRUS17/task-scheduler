<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817100342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_default flag to workflow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workflow ADD is_default BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_workflow_created_by_default ON workflow (created_by) '
                . 'WHERE is_default = TRUE',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_workflow_created_by_default');
        $this->addSql('ALTER TABLE workflow DROP COLUMN is_default');
    }
}
