<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507830421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace subscription channels bitmask with subscription_channel join table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE subscription_channel (
                subscription_id VARCHAR(36) NOT NULL,
                channel         VARCHAR(30) NOT NULL,
                PRIMARY KEY (subscription_id, channel)
            )
        ');

        $this->addSql('
            ALTER TABLE subscription_channel
            ADD CONSTRAINT fk_subscription_channel_subscription
            FOREIGN KEY (subscription_id) REFERENCES subscription (id) ON DELETE CASCADE
        ');

        $this->addSql("
            INSERT INTO subscription_channel (subscription_id, channel)
            SELECT id, 'email' FROM subscription WHERE (channels & 1) != 0
        ");

        $this->addSql("
            INSERT INTO subscription_channel (subscription_id, channel)
            SELECT id, 'in_app' FROM subscription WHERE (channels & 2) != 0
        ");

        $this->addSql('ALTER TABLE subscription DROP COLUMN channels');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription ADD COLUMN channels SMALLINT NOT NULL DEFAULT 0');

        $this->addSql("
            UPDATE subscription s SET channels = (
                SELECT COALESCE(SUM(
                    CASE channel WHEN 'email' THEN 1 WHEN 'in_app' THEN 2 ELSE 0 END
                ), 0)
                FROM subscription_channel sc WHERE sc.subscription_id = s.id
            )
        ");

        $this->addSql('DROP TABLE subscription_channel');
    }
}
