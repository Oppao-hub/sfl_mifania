<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer_address table, order shipping snapshots, and migrate legacy customer addresses';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('CREATE TABLE customer_address (
            id INT AUTO_INCREMENT NOT NULL,
            customer_id INT NOT NULL,
            label VARCHAR(80) NOT NULL,
            recipient_first_name VARCHAR(100) DEFAULT NULL,
            recipient_last_name VARCHAR(100) DEFAULT NULL,
            contact_number VARCHAR(20) DEFAULT NULL,
            address LONGTEXT NOT NULL,
            city VARCHAR(100) DEFAULT NULL,
            state VARCHAR(100) DEFAULT NULL,
            country VARCHAR(100) DEFAULT NULL,
            postal_code VARCHAR(20) DEFAULT NULL,
            is_default TINYINT(1) NOT NULL DEFAULT 0,
            has_pinpoint TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CUSTOMER_ADDRESS_CUSTOMER (customer_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE customer_address ADD CONSTRAINT FK_CUSTOMER_ADDRESS_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');

        $orderColumns = $this->connection->fetchFirstColumn(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders'"
        );
        $orderColumnSet = array_flip($orderColumns);

        if (!isset($orderColumnSet['customer_address_id'])) {
            $this->addSql('ALTER TABLE orders ADD customer_address_id INT DEFAULT NULL');
        }
        if (!isset($orderColumnSet['shipping_label'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_label VARCHAR(80) DEFAULT NULL');
        }
        if (!isset($orderColumnSet['shipping_recipient_name'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_recipient_name VARCHAR(200) DEFAULT NULL');
        }
        if (!isset($orderColumnSet['shipping_contact_number'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_contact_number VARCHAR(20) DEFAULT NULL');
        }
        if (!isset($orderColumnSet['shipping_address_line'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_address_line LONGTEXT DEFAULT NULL');
        }
        if (!isset($orderColumnSet['shipping_city'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_city VARCHAR(100) DEFAULT NULL');
        }
        if (!isset($orderColumnSet['shipping_state'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_state VARCHAR(100) DEFAULT NULL');
        }
        if (!isset($orderColumnSet['shipping_country'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_country VARCHAR(100) DEFAULT NULL');
        }
        if (!isset($orderColumnSet['shipping_postal_code'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_postal_code VARCHAR(20) DEFAULT NULL');
        }

        $constraints = $this->connection->fetchFirstColumn(
            "SELECT constraint_name FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'orders' AND constraint_name = 'FK_ORDER_CUSTOMER_ADDRESS'"
        );
        if ($constraints === []) {
            $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_ORDER_CUSTOMER_ADDRESS FOREIGN KEY (customer_address_id) REFERENCES customer_address (id) ON DELETE SET NULL');
        }

        $this->addSql("INSERT INTO customer_address (
            customer_id, label, recipient_first_name, recipient_last_name, contact_number,
            address, city, state, country, postal_code, is_default, has_pinpoint, created_at, updated_at
        )
        SELECT
            c.id,
            'Home',
            c.first_name,
            c.last_name,
            c.contact_number,
            c.address,
            c.city,
            c.state,
            c.country,
            c.postal_code,
            1,
            1,
            NOW(),
            NOW()
        FROM customer c
        WHERE c.address IS NOT NULL AND TRIM(c.address) <> ''");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_ORDER_CUSTOMER_ADDRESS');
        $this->addSql('ALTER TABLE orders DROP customer_address_id');
        $this->addSql('ALTER TABLE orders DROP shipping_label');
        $this->addSql('ALTER TABLE orders DROP shipping_recipient_name');
        $this->addSql('ALTER TABLE orders DROP shipping_contact_number');
        $this->addSql('ALTER TABLE orders DROP shipping_address_line');
        $this->addSql('ALTER TABLE orders DROP shipping_city');
        $this->addSql('ALTER TABLE orders DROP shipping_state');
        $this->addSql('ALTER TABLE orders DROP shipping_country');
        $this->addSql('ALTER TABLE orders DROP shipping_postal_code');
        $this->addSql('DROP TABLE customer_address');
    }
}
