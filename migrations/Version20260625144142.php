<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625144142 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password_reset_code and password_reset_expires_at to user for forgot-password flow';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD password_reset_code VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD password_reset_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP password_reset_code');
        $this->addSql('ALTER TABLE "user" DROP password_reset_expires_at');
    }
}
