<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add uiSettings JSON field to User entity for storing user preferences
 */
final class Version20250929200013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uiSettings JSON field to User entity for storing user preferences';
    }

    public function up(Schema $schema): void
    {
        // Add uiSettings JSON column to user table
        $this->addSql('ALTER TABLE "user" ADD ui_settings JSON DEFAULT NULL');

        // Add comment for documentation
        $this->addSql('COMMENT ON COLUMN "user".ui_settings IS \'JSON field storing user UI preferences\'');
    }

    public function down(Schema $schema): void
    {
        // Remove uiSettings column
        $this->addSql('ALTER TABLE "user" DROP ui_settings');
    }
}