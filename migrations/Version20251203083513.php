<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203083513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item RENAME INDEX idx_52ea1f09a15a2e17 TO IDX_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE reward_transaction DROP FOREIGN KEY FK_67A2E803A15A2E17');
        $this->addSql('DROP INDEX IDX_67A2E803A15A2E17 ON reward_transaction');
        $this->addSql('ALTER TABLE reward_transaction CHANGE customer_order_id order_id INT NOT NULL');
        $this->addSql('ALTER TABLE reward_transaction ADD CONSTRAINT FK_67A2E8038D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_67A2E8038D9F6D38 ON reward_transaction (order_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_item RENAME INDEX idx_52ea1f098d9f6d38 TO IDX_52EA1F09A15A2E17');
        $this->addSql('ALTER TABLE reward_transaction DROP FOREIGN KEY FK_67A2E8038D9F6D38');
        $this->addSql('DROP INDEX IDX_67A2E8038D9F6D38 ON reward_transaction');
        $this->addSql('ALTER TABLE reward_transaction CHANGE order_id customer_order_id INT NOT NULL');
        $this->addSql('ALTER TABLE reward_transaction ADD CONSTRAINT FK_67A2E803A15A2E17 FOREIGN KEY (customer_order_id) REFERENCES `order` (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_67A2E803A15A2E17 ON reward_transaction (customer_order_id)');
    }
}
