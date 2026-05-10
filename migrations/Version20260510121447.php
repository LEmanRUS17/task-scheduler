<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510121447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create audit_log table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE audit_log (
                id           VARCHAR(36)  NOT NULL,
                entity_class VARCHAR(255) NOT NULL,
                entity_id    VARCHAR(36)  NOT NULL,
                action       VARCHAR(10)  NOT NULL,
                changed_data JSON         NOT NULL,
                actor_id     VARCHAR(36)  DEFAULT NULL,
                occurred_at  TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log');
    }
}
