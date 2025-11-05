<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251104042201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer_disable (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, date_of_birth DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', contact_number VARCHAR(20) NOT NULL, address LONGTEXT NOT NULL, city VARCHAR(100) NOT NULL, country VARCHAR(100) NOT NULL, state VARCHAR(100) NOT NULL, account_status VARCHAR(50) NOT NULL, verification_status VARCHAR(50) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, reward_points INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_5398E9EFA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE customer_disable ADD CONSTRAINT FK_5398E9EFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer ADD bio VARCHAR(255) DEFAULT NULL, ADD postal_code VARCHAR(20) DEFAULT NULL, ADD tax_id VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_disable DROP FOREIGN KEY FK_5398E9EFA76ED395');
        $this->addSql('DROP TABLE customer_disable');
        $this->addSql('ALTER TABLE customer DROP bio, DROP postal_code, DROP tax_id');
    }
}
