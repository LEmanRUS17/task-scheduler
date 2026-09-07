<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260825120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on audit_log(actor_id, occurred_at) for personal activity queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_audit_log_actor_id_occurred_at ON audit_log (actor_id, occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_audit_log_actor_id_occurred_at');
    }
}
