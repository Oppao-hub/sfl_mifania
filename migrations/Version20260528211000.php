<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528211000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add loyalty redemption and idempotency fields to orders';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql("ALTER TABLE orders ADD points_redeemed INT NOT NULL DEFAULT 0, ADD discount_amount NUMERIC(10, 2) NOT NULL DEFAULT '0.00', ADD original_amount NUMERIC(10, 2) NOT NULL DEFAULT '0.00', ADD idempotency_key VARCHAR(128) DEFAULT NULL");
        $this->addSql('CREATE UNIQUE INDEX uniq_order_customer_idempotency ON orders (customer_id, idempotency_key)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('DROP INDEX uniq_order_customer_idempotency ON orders');
        $this->addSql('ALTER TABLE orders DROP points_redeemed, DROP discount_amount, DROP original_amount, DROP idempotency_key');
    }
}
