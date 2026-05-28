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

        $columns = $this->connection->fetchFirstColumn(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'orders'"
        );
        $columnSet = array_flip($columns);

        if (!isset($columnSet['points_redeemed'])) {
            $this->addSql('ALTER TABLE orders ADD points_redeemed INT NOT NULL DEFAULT 0');
        }
        if (!isset($columnSet['discount_amount'])) {
            $this->addSql("ALTER TABLE orders ADD discount_amount NUMERIC(10, 2) NOT NULL DEFAULT '0.00'");
        }
        if (!isset($columnSet['original_amount'])) {
            $this->addSql("ALTER TABLE orders ADD original_amount NUMERIC(10, 2) NOT NULL DEFAULT '0.00'");
        }
        if (!isset($columnSet['idempotency_key'])) {
            $this->addSql('ALTER TABLE orders ADD idempotency_key VARCHAR(128) DEFAULT NULL');
        }

        $indexExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'orders' AND index_name = 'uniq_order_customer_idempotency'"
        );
        if ($indexExists === 0) {
            $this->addSql('CREATE UNIQUE INDEX uniq_order_customer_idempotency ON orders (customer_id, idempotency_key)');
        }
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
