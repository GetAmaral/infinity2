<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002181012 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organization_id to student_course table for data consistency and filtering';
    }

    public function up(Schema $schema): void
    {
        // Step 1: Add organization_id column as nullable
        $this->addSql('ALTER TABLE student_course ADD organization_id UUID');
        $this->addSql('COMMENT ON COLUMN student_course.organization_id IS \'(DC2Type:uuid)\'');

        // Step 2: Populate organization_id from course.organization_id for existing records
        $this->addSql('
            UPDATE student_course sc
            SET organization_id = c.organization_id
            FROM course c
            WHERE sc.course_id = c.id
        ');

        // Step 3: Make organization_id NOT NULL
        $this->addSql('ALTER TABLE student_course ALTER COLUMN organization_id SET NOT NULL');

        // Step 4: Add foreign key constraint and index
        $this->addSql('ALTER TABLE student_course ADD CONSTRAINT FK_98A8B73932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_98A8B73932C8A3DE ON student_course (organization_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE student_course DROP CONSTRAINT FK_98A8B73932C8A3DE');
        $this->addSql('DROP INDEX IDX_98A8B73932C8A3DE');
        $this->addSql('ALTER TABLE student_course DROP organization_id');
    }
}
