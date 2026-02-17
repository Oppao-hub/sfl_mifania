<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211042233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log ADD impersonator_id INT DEFAULT NULL, ADD table_name VARCHAR(255) DEFAULT NULL, ADD data JSON DEFAULT NULL, CHANGE details description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F647D1107CFF FOREIGN KEY (impersonator_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_FD06F647D1107CFF ON activity_log (impersonator_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F647D1107CFF');
        $this->addSql('DROP INDEX IDX_FD06F647D1107CFF ON activity_log');
        $this->addSql('ALTER TABLE activity_log DROP impersonator_id, DROP table_name, DROP data, CHANGE description details LONGTEXT DEFAULT NULL');
    }
}
