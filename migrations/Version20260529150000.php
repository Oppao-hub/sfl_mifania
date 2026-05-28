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
        return 'Add optional courier note to customer_address';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $columns = $this->connection->fetchFirstColumn(
            "SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'customer_address'"
        );

        if (!in_array('courier_note', $columns, true)) {
            $this->addSql('ALTER TABLE customer_address ADD courier_note LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof MySQLPlatform,
            'Migration can only be executed safely on mysql.',
        );

        $this->addSql('ALTER TABLE customer_address DROP courier_note');
    }
}
