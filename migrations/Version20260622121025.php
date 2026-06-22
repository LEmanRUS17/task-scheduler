<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622121025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add confirmation_code and code_expires_at to user for email registration confirmation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD confirmation_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD code_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP confirmation_code');
        $this->addSql('ALTER TABLE "user" DROP code_expires_at');
    }
}
