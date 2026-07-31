<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add team_invitation table for team member invitations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE team_invitation ('
                . 'id VARCHAR(36) NOT NULL, '
                . 'team_id VARCHAR(36) NOT NULL, '
                . 'invited_user_id VARCHAR(36) NOT NULL, '
                . 'invited_by_user_id VARCHAR(36) NOT NULL, '
                . 'role VARCHAR(255) NOT NULL, '
                . 'status VARCHAR(255) NOT NULL, '
                . 'token VARCHAR(64) NOT NULL, '
                . 'created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, '
                . 'expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, '
                . 'responded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, '
                . 'PRIMARY KEY (id)'
                . ')',
        );
        $this->addSql('CREATE UNIQUE INDEX uniq_team_invitation_token ON team_invitation (token)');
        $this->addSql(
            'CREATE INDEX idx_team_invitation_team_user ON team_invitation (team_id, invited_user_id, status)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE team_invitation');
    }
}
