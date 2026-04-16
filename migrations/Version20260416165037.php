<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416165037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create team table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE team (
            id VARCHAR(36) NOT NULL, 
            title VARCHAR(255) NOT NULL, 
            status VARCHAR(255) NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            PRIMARY KEY (id))'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE team');
    }
}
