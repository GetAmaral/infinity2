<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add audit logging fields to entities
 */
final class Version20250929184500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit logging fields (createdBy, updatedBy) to User and Organization entities';
    }

    public function up(Schema $schema): void
    {
        // Add audit user references to user table
        $this->addSql('ALTER TABLE "user" ADD created_by_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD updated_by_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN "user".created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_by_id IS \'(DC2Type:uuid)\'');

        // Add audit user references to organization table
        $this->addSql('ALTER TABLE organization ADD created_by_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE organization ADD updated_by_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN organization.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN organization.updated_by_id IS \'(DC2Type:uuid)\'');

        // Add foreign key constraints (with SET NULL on delete to preserve audit trail)
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_USER_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_USER_UPDATED_BY FOREIGN KEY (updated_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_ORG_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_ORG_UPDATED_BY FOREIGN KEY (updated_by_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add indexes for better query performance
        $this->addSql('CREATE INDEX IDX_USER_CREATED_BY ON "user" (created_by_id)');
        $this->addSql('CREATE INDEX IDX_USER_UPDATED_BY ON "user" (updated_by_id)');
        $this->addSql('CREATE INDEX IDX_ORG_CREATED_BY ON organization (created_by_id)');
        $this->addSql('CREATE INDEX IDX_ORG_UPDATED_BY ON organization (updated_by_id)');

        // Add index on created_at for audit queries
        $this->addSql('CREATE INDEX IDX_USER_CREATED_AT ON "user" (created_at)');
        $this->addSql('CREATE INDEX IDX_ORG_CREATED_AT ON organization (created_at)');
    }

    public function down(Schema $schema): void
    {
        // Drop indexes
        $this->addSql('DROP INDEX IDX_USER_CREATED_BY');
        $this->addSql('DROP INDEX IDX_USER_UPDATED_BY');
        $this->addSql('DROP INDEX IDX_ORG_CREATED_BY');
        $this->addSql('DROP INDEX IDX_ORG_UPDATED_BY');
        $this->addSql('DROP INDEX IDX_USER_CREATED_AT');
        $this->addSql('DROP INDEX IDX_ORG_CREATED_AT');

        // Drop foreign key constraints
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_USER_CREATED_BY');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_USER_UPDATED_BY');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_ORG_CREATED_BY');
        $this->addSql('ALTER TABLE organization DROP CONSTRAINT FK_ORG_UPDATED_BY');

        // Remove audit fields
        $this->addSql('ALTER TABLE "user" DROP created_by_id');
        $this->addSql('ALTER TABLE "user" DROP updated_by_id');
        $this->addSql('ALTER TABLE organization DROP created_by_id');
        $this->addSql('ALTER TABLE organization DROP updated_by_id');
    }
}