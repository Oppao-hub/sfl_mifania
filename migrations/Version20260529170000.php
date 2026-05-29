<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add wallet_top_up_intent table for PayPal wallet top-ups';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $tables = $this->connection->createSchemaManager()->listTableNames();
        if (in_array('wallet_top_up_intent', $tables, true)) {
            return;
        }

        $this->addSql('CREATE TABLE wallet_top_up_intent (
            id INT AUTO_INCREMENT NOT NULL,
            customer_id INT NOT NULL,
            amount NUMERIC(10, 2) NOT NULL,
            prepare_token VARCHAR(64) NOT NULL,
            paypal_order_id VARCHAR(64) DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            UNIQUE INDEX UNIQ_WTUI_PREPARE_TOKEN (prepare_token),
            UNIQUE INDEX UNIQ_WTUI_PAYPAL_ORDER (paypal_order_id),
            INDEX IDX_WTUI_CUSTOMER (customer_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_WTUI_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('DROP TABLE IF EXISTS wallet_top_up_intent');
    }
}
