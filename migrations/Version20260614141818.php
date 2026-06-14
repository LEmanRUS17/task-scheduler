<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614141818 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Widen audit_log.entity_id to support composite identifiers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log ALTER COLUMN entity_id TYPE VARCHAR(255)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log ALTER COLUMN entity_id TYPE VARCHAR(36)');
    }
}
