<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add listPreferences field to user table
 */
final class Version20250930040500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add listPreferences JSON field to user table for storing list view state';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD list_preferences JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP list_preferences');
    }
}