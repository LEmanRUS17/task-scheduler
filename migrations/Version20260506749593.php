<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260506749593 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create subscription and subscription_transition tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE subscription (
                id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                subject_type VARCHAR(50) NOT NULL,
                subject_id VARCHAR(36) NOT NULL,
                channels SMALLINT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY (id)
            )
        ');

        $this->addSql('
            CREATE UNIQUE INDEX uq_subscription_user_subject ON subscription (user_id, subject_type, subject_id)
        ');

        $this->addSql('
            CREATE INDEX idx_subscription_subject ON subscription (subject_type, subject_id)
        ');

        $this->addSql('
            CREATE TABLE subscription_transition (
                subscription_id VARCHAR(36) NOT NULL,
                workflow_transition_id VARCHAR(36) NOT NULL,
                PRIMARY KEY (subscription_id, workflow_transition_id)
            )
        ');

        $this->addSql('
            ALTER TABLE subscription_transition
            ADD CONSTRAINT fk_subscription_transition_subscription
            FOREIGN KEY (subscription_id) REFERENCES subscription (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subscription_transition');
        $this->addSql('DROP TABLE subscription');
    }
}
