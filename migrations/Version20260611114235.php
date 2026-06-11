<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611114235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create descriptions table for DescriptionFeature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE descriptions (id VARCHAR(36) NOT NULL, entity_class VARCHAR(255) NOT NULL, entity_id VARCHAR(36) NOT NULL, content TEXT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX uq_description_entity ON descriptions (entity_class, entity_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE descriptions');
    }
}
