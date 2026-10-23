<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * The log of the sends of electronic invoices (US-180). The invoice id is the primary key: one line
 * per invoice, which makes the listener idempotent even when two processes handle the same event.
 * No foreign key to the invoice: the log must not depend on the Invoicing plugin tables. Schema
 * API: the same migration on MySQL, MariaDB and PostgreSQL.
 */
final class Version20261024100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Log of the transmissions of electronic invoices.';
    }

    public function up(Schema $schema): void
    {
        $log = $schema->createTable('tmonier_einvoice_transmission');
        $log->addColumn('invoice_id', Types::STRING, ['length' => 255]);
        $log->addColumn('invoice_number', Types::STRING, ['length' => 255]);
        $log->addColumn('status', Types::STRING, ['length' => 64]);
        $log->addColumn('message', Types::TEXT, ['notnull' => false]);
        $log->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $log->addColumn('updated_at', Types::DATETIME_IMMUTABLE);
        $log->setPrimaryKey(['invoice_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('tmonier_einvoice_transmission');
    }
}
