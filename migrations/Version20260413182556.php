<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260413182556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create profile table with foreign key to user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE profile (
            user_id VARCHAR(36) NOT NULL, 
            username VARCHAR(255) DEFAULT NULL, 
            firstname VARCHAR(255) DEFAULT NULL, 
            lastname VARCHAR(255) DEFAULT NULL, 
            midlname VARCHAR(255) DEFAULT NULL, 
            status VARCHAR(255) DEFAULT NULL, 
            last_login TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            PRIMARY KEY (user_id))'
        );
        $this->addSql(
            'ALTER TABLE profile ADD CONSTRAINT FK_8157AA0FA76ED395 
            FOREIGN KEY (user_id) REFERENCES "user" (id) 
            NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profile DROP CONSTRAINT FK_8157AA0FA76ED395');
        $this->addSql('DROP TABLE profile');
    }
}
