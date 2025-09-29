<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add authentication fields to User entity and create Role entity with user_roles junction table
 */
final class Version20250929181500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add authentication system: Role entity, user authentication fields, and user_roles relationship';
    }

    public function up(Schema $schema): void
    {
        // Create role table
        $this->addSql('CREATE TABLE role (
            id UUID NOT NULL,
            name VARCHAR(50) NOT NULL,
            description VARCHAR(255) NOT NULL,
            permissions JSON NOT NULL,
            is_system BOOLEAN NOT NULL DEFAULT false,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ROLE_NAME ON role (name)');
        $this->addSql('COMMENT ON COLUMN role.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN role.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN role.updated_at IS \'(DC2Type:datetime_immutable)\'');

        // Add authentication fields to user table
        $this->addSql('ALTER TABLE "user" ADD password VARCHAR(255) NOT NULL DEFAULT \'\'');
        $this->addSql('ALTER TABLE "user" ADD is_verified BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE "user" ADD verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD api_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD api_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD failed_login_attempts INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE "user" ADD locked_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');

        $this->addSql('COMMENT ON COLUMN "user".api_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".last_login_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".locked_until IS \'(DC2Type:datetime_immutable)\'');

        // Create user_roles junction table
        $this->addSql('CREATE TABLE user_roles (
            user_id UUID NOT NULL,
            role_id UUID NOT NULL,
            PRIMARY KEY(user_id, role_id)
        )');
        $this->addSql('CREATE INDEX IDX_USER_ROLES_USER ON user_roles (user_id)');
        $this->addSql('CREATE INDEX IDX_USER_ROLES_ROLE ON user_roles (role_id)');
        $this->addSql('COMMENT ON COLUMN user_roles.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN user_roles.role_id IS \'(DC2Type:uuid)\'');

        // Add foreign keys
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_USER_ROLES_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_USER_ROLES_ROLE FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign keys and junction table
        $this->addSql('ALTER TABLE user_roles DROP CONSTRAINT FK_USER_ROLES_USER');
        $this->addSql('ALTER TABLE user_roles DROP CONSTRAINT FK_USER_ROLES_ROLE');
        $this->addSql('DROP TABLE user_roles');

        // Remove authentication fields from user table
        $this->addSql('ALTER TABLE "user" DROP password');
        $this->addSql('ALTER TABLE "user" DROP is_verified');
        $this->addSql('ALTER TABLE "user" DROP verification_token');
        $this->addSql('ALTER TABLE "user" DROP api_token');
        $this->addSql('ALTER TABLE "user" DROP api_token_expires_at');
        $this->addSql('ALTER TABLE "user" DROP last_login_at');
        $this->addSql('ALTER TABLE "user" DROP failed_login_attempts');
        $this->addSql('ALTER TABLE "user" DROP locked_until');

        // Drop role table
        $this->addSql('DROP TABLE role');
    }
}