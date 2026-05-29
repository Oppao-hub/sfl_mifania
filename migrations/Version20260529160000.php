<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product_review table for order product ratings';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('CREATE TABLE product_review (
            id INT AUTO_INCREMENT NOT NULL,
            customer_id INT NOT NULL,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            rating INT NOT NULL,
            comment LONGTEXT DEFAULT NULL,
            reviewer_name VARCHAR(120) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_PRODUCT_REVIEW_CUSTOMER (customer_id),
            INDEX IDX_PRODUCT_REVIEW_ORDER (order_id),
            INDEX IDX_PRODUCT_REVIEW_PRODUCT (product_id),
            UNIQUE INDEX uniq_review_customer_order_product (customer_id, order_id, product_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_PRODUCT_REVIEW_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_PRODUCT_REVIEW_ORDER FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_review ADD CONSTRAINT FK_PRODUCT_REVIEW_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_PRODUCT_REVIEW_CUSTOMER');
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_PRODUCT_REVIEW_ORDER');
        $this->addSql('ALTER TABLE product_review DROP FOREIGN KEY FK_PRODUCT_REVIEW_PRODUCT');
        $this->addSql('DROP TABLE product_review');
    }
}
