<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The table was created with the default collation of the database. On MySQL 8 that is utf8mb4_0900_ai_ci, while the
 * Sylius tables are utf8mb4_unicode_ci, and comparing the invoice identifier with the Invoicing plugin table then fails
 * ("Illegal mix of collations"). The table gets the collation of the Sylius ones. MySQL and MariaDB only.
 */
final class Version20261025100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give the transmission log the collation of the Sylius tables (MySQL and MariaDB).';
    }

    public function up(Schema $schema): void
    {
        $this->skipIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Collations are a MySQL and MariaDB matter.');

        $this->addSql('ALTER TABLE tmonier_einvoice_transmission CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        // The previous collation was the default of the database: nothing worth restoring.
    }
}
