<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504024020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `admin` DROP FOREIGN KEY `FK_880E0D76A76ED395`');
        $this->addSql('ALTER TABLE `admin` ADD CONSTRAINT FK_880E0D76A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `FK_BA388B79395C3F3`');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B79395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY `FK_81398E09A76ED395`');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY `FK_BF5476CAE92F8F78`');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE redemption DROP FOREIGN KEY `FK_2C6134239395C3F3`');
        $this->addSql('ALTER TABLE redemption ADD CONSTRAINT FK_2C6134239395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY `FK_7CE748AA76ED395`');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reward_transaction DROP FOREIGN KEY `FK_67A2E8039395C3F3`');
        $this->addSql('ALTER TABLE reward_transaction ADD CONSTRAINT FK_67A2E8039395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY `FK_426EF392A76ED395`');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF392A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY `FK_4B36566055B127A4`');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY `FK_4B365660896DBBDE`');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B36566055B127A4 FOREIGN KEY (added_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE wallet DROP FOREIGN KEY `FK_7C68921F9395C3F3`');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921F9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `admin` DROP FOREIGN KEY FK_880E0D76A76ED395');
        $this->addSql('ALTER TABLE `admin` ADD CONSTRAINT `FK_880E0D76A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B79395C3F3');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `FK_BA388B79395C3F3` FOREIGN KEY (customer_id) REFERENCES customer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09A76ED395');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT `FK_81398E09A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT `FK_BF5476CAE92F8F78` FOREIGN KEY (recipient_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE redemption DROP FOREIGN KEY FK_2C6134239395C3F3');
        $this->addSql('ALTER TABLE redemption ADD CONSTRAINT `FK_2C6134239395C3F3` FOREIGN KEY (customer_id) REFERENCES customer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT `FK_7CE748AA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE reward_transaction DROP FOREIGN KEY FK_67A2E8039395C3F3');
        $this->addSql('ALTER TABLE reward_transaction ADD CONSTRAINT `FK_67A2E8039395C3F3` FOREIGN KEY (customer_id) REFERENCES customer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF392A76ED395');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT `FK_426EF392A76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B36566055B127A4');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660896DBBDE');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT `FK_4B36566055B127A4` FOREIGN KEY (added_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT `FK_4B365660896DBBDE` FOREIGN KEY (updated_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE wallet DROP FOREIGN KEY FK_7C68921F9395C3F3');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT `FK_7C68921F9395C3F3` FOREIGN KEY (customer_id) REFERENCES customer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
