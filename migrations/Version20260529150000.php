<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer_payment_method table for saved payment methods';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $tables = $this->connection->createSchemaManager()->listTableNames();
        if (in_array('customer_payment_method', $tables, true)) {
            return;
        }

        $this->addSql('CREATE TABLE customer_payment_method (
            id INT AUTO_INCREMENT NOT NULL,
            customer_id INT NOT NULL,
            provider_type VARCHAR(32) NOT NULL,
            card_brand VARCHAR(32) DEFAULT NULL,
            last_four VARCHAR(4) DEFAULT NULL,
            expiry_month INT DEFAULT NULL,
            expiry_year INT DEFAULT NULL,
            holder_name VARCHAR(120) DEFAULT NULL,
            is_connected TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_CPM_CUSTOMER (customer_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_CPM_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('DROP TABLE customer_payment_method');
    }
}
