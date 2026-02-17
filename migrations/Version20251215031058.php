<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251215031058 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer CHANGE contact_number contact_number VARCHAR(20) DEFAULT NULL, CHANGE address address LONGTEXT DEFAULT NULL, CHANGE city city VARCHAR(100) DEFAULT NULL, CHANGE country country VARCHAR(100) DEFAULT NULL, CHANGE state state VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer CHANGE contact_number contact_number VARCHAR(20) NOT NULL, CHANGE address address LONGTEXT NOT NULL, CHANGE city city VARCHAR(100) NOT NULL, CHANGE country country VARCHAR(100) NOT NULL, CHANGE state state VARCHAR(100) NOT NULL');
    }
}
