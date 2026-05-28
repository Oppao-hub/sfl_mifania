<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename reserved MySQL table `order` to `orders`';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'order'"
        );
        $renamedExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'orders'"
        );

        if ($tableExists === 1 && $renamedExists === 0) {
        // `order` is a reserved keyword in MySQL and breaks some SQL tooling.
            $this->addSql('RENAME TABLE `order` TO `orders`');
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $tableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'orders'"
        );
        $oldTableExists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'order'"
        );

        if ($tableExists === 1 && $oldTableExists === 0) {
            $this->addSql('RENAME TABLE `orders` TO `order`');
        }
    }
}
