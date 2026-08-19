<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260818163231 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create workflow_team table linking workflows attached to teams';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE workflow_team (
              team_id VARCHAR(36) NOT NULL,
              workflow_id VARCHAR(36) NOT NULL,
              attached_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              PRIMARY KEY (team_id, workflow_id)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE workflow_team');
    }
}
