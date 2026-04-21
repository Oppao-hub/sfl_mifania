<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260408022001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD assigned_support_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6493BB4D774 FOREIGN KEY (assigned_support_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8D93D6493BB4D774 ON user (assigned_support_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6493BB4D774');
        $this->addSql('DROP INDEX IDX_8D93D6493BB4D774 ON user');
        $this->addSql('ALTER TABLE user DROP assigned_support_id');
    }
}
