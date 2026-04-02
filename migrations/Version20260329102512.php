<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260329102512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(100) NOT NULL, target_data LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, role VARCHAR(50) NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_FD06F647A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `admin` (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_880E0D76A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, total_quantity INT NOT NULL, total_price NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, customer_id INT DEFAULT NULL, INDEX IDX_BA388B79395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 2) NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, cart_id INT DEFAULT NULL, product_id INT DEFAULT NULL, INDEX IDX_F0FE25271AD5CDBF (cart_id), INDEX IDX_F0FE25274584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, description LONGTEXT NOT NULL, slug VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_64C19C1989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, contact_number VARCHAR(20) DEFAULT NULL, address LONGTEXT DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, country VARCHAR(100) DEFAULT NULL, state VARCHAR(100) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_81398E09A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_product (customer_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_CF97A0139395C3F3 (customer_id), INDEX IDX_CF97A0134584665A (product_id), PRIMARY KEY (customer_id, product_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT DEFAULT NULL, is_read TINYINT NOT NULL, created_at DATE NOT NULL, target_url VARCHAR(255) DEFAULT NULL, recipient_id INT DEFAULT NULL, INDEX IDX_BF5476CAE92F8F78 (recipient_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, payment_method VARCHAR(50) NOT NULL, payment_status VARCHAR(50) NOT NULL, order_status VARCHAR(50) NOT NULL, reward_points INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, customer_id INT DEFAULT NULL, INDEX IDX_F52993989395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 2) NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, order_id INT DEFAULT NULL, product_id INT DEFAULT NULL, INDEX IDX_52EA1F098D9F6D38 (order_id), INDEX IDX_52EA1F094584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, material VARCHAR(100) NOT NULL, size VARCHAR(20) NOT NULL, color VARCHAR(50) NOT NULL, price NUMERIC(10, 2) NOT NULL, cost NUMERIC(10, 2) NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, eco_info LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, sub_category_id INT NOT NULL, story_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_D34A04AD989D9B62 (slug), INDEX IDX_D34A04ADF7BFE87C (sub_category_id), INDEX IDX_D34A04ADAA5D4036 (story_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE qrtag (id INT AUTO_INCREMENT NOT NULL, qr_code_value LONGTEXT DEFAULT NULL, date_generated DATETIME NOT NULL, qr_image_path VARCHAR(255) NOT NULL, product_id INT NOT NULL, UNIQUE INDEX UNIQ_3F6E38E44584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE redemption (id INT AUTO_INCREMENT NOT NULL, point_spent INT NOT NULL, redeemed_at DATETIME NOT NULL, status VARCHAR(50) NOT NULL, customer_id INT DEFAULT NULL, reward_id INT NOT NULL, INDEX IDX_2C6134239395C3F3 (customer_id), INDEX IDX_2C613423E466ACA1 (reward_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reward (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, points_required INT NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reward_transaction (id INT AUTO_INCREMENT NOT NULL, points INT NOT NULL, type VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, customer_id INT NOT NULL, order_id INT NOT NULL, INDEX IDX_67A2E8039395C3F3 (customer_id), INDEX IDX_67A2E8038D9F6D38 (order_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE staff (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_426EF392A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, location VARCHAR(255) DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, product_id INT DEFAULT NULL, added_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_4B3656604584665A (product_id), INDEX IDX_4B36566055B127A4 (added_by_id), INDEX IDX_4B365660896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE story (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(100) NOT NULL, material_content LONGTEXT NOT NULL, artisan_content LONGTEXT NOT NULL, dyeing_content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sub_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INT NOT NULL, UNIQUE INDEX UNIQ_BCE3F798989D9B62 (slug), INDEX IDX_BCE3F79812469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, is_verified TINYINT NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE wallet (id INT AUTO_INCREMENT NOT NULL, balance NUMERIC(10, 2) NOT NULL, reward_points INT NOT NULL, customer_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_7C68921F9395C3F3 (customer_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE wallet_transaction (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, type VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, wallet_id INT NOT NULL, INDEX IDX_7DAF972712520F3 (wallet_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F647A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `admin` ADD CONSTRAINT FK_880E0D76A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B79395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25274584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE customer_product ADD CONSTRAINT FK_CF97A0139395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_product ADD CONSTRAINT FK_CF97A0134584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAE92F8F78 FOREIGN KEY (recipient_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADF7BFE87C FOREIGN KEY (sub_category_id) REFERENCES sub_category (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADAA5D4036 FOREIGN KEY (story_id) REFERENCES story (id)');
        $this->addSql('ALTER TABLE qrtag ADD CONSTRAINT FK_3F6E38E44584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE redemption ADD CONSTRAINT FK_2C6134239395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE redemption ADD CONSTRAINT FK_2C613423E466ACA1 FOREIGN KEY (reward_id) REFERENCES reward (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reward_transaction ADD CONSTRAINT FK_67A2E8039395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE reward_transaction ADD CONSTRAINT FK_67A2E8038D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE staff ADD CONSTRAINT FK_426EF392A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B3656604584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B36566055B127A4 FOREIGN KEY (added_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE sub_category ADD CONSTRAINT FK_BCE3F79812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_7C68921F9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE wallet_transaction ADD CONSTRAINT FK_7DAF972712520F3 FOREIGN KEY (wallet_id) REFERENCES wallet (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F647A76ED395');
        $this->addSql('ALTER TABLE `admin` DROP FOREIGN KEY FK_880E0D76A76ED395');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B79395C3F3');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25274584665A');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09A76ED395');
        $this->addSql('ALTER TABLE customer_product DROP FOREIGN KEY FK_CF97A0139395C3F3');
        $this->addSql('ALTER TABLE customer_product DROP FOREIGN KEY FK_CF97A0134584665A');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAE92F8F78');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989395C3F3');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADF7BFE87C');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADAA5D4036');
        $this->addSql('ALTER TABLE qrtag DROP FOREIGN KEY FK_3F6E38E44584665A');
        $this->addSql('ALTER TABLE redemption DROP FOREIGN KEY FK_2C6134239395C3F3');
        $this->addSql('ALTER TABLE redemption DROP FOREIGN KEY FK_2C613423E466ACA1');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reward_transaction DROP FOREIGN KEY FK_67A2E8039395C3F3');
        $this->addSql('ALTER TABLE reward_transaction DROP FOREIGN KEY FK_67A2E8038D9F6D38');
        $this->addSql('ALTER TABLE staff DROP FOREIGN KEY FK_426EF392A76ED395');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B3656604584665A');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B36566055B127A4');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660896DBBDE');
        $this->addSql('ALTER TABLE sub_category DROP FOREIGN KEY FK_BCE3F79812469DE2');
        $this->addSql('ALTER TABLE wallet DROP FOREIGN KEY FK_7C68921F9395C3F3');
        $this->addSql('ALTER TABLE wallet_transaction DROP FOREIGN KEY FK_7DAF972712520F3');
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('DROP TABLE `admin`');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE customer_product');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE qrtag');
        $this->addSql('DROP TABLE redemption');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE reward');
        $this->addSql('DROP TABLE reward_transaction');
        $this->addSql('DROP TABLE staff');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE story');
        $this->addSql('DROP TABLE sub_category');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE wallet');
        $this->addSql('DROP TABLE wallet_transaction');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
