<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shipping and order notes fields to orders';
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

        if (!isset($columnSet['shipping_method'])) {
            $this->addSql('ALTER TABLE orders ADD shipping_method VARCHAR(120) DEFAULT NULL');
        }
        if (!isset($columnSet['shipping_fee'])) {
            $this->addSql("ALTER TABLE orders ADD shipping_fee NUMERIC(10, 2) NOT NULL DEFAULT '0.00'");
        }
        if (!isset($columnSet['order_notes'])) {
            $this->addSql('ALTER TABLE orders ADD order_notes LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('ALTER TABLE orders DROP shipping_method');
        $this->addSql('ALTER TABLE orders DROP shipping_fee');
        $this->addSql('ALTER TABLE orders DROP order_notes');
    }
}
