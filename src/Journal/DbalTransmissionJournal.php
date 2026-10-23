<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Journal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Clock\ClockInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatusCatalog;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;

/**
 * Writes with DBAL and not with the entity manager: a refused insert (the invoice is already
 * logged) closes an entity manager, and this one is shared with the generation of the invoice.
 */
final readonly class DbalTransmissionJournal implements TransmissionJournalInterface
{
    public const TABLE = 'tmonier_einvoice_transmission';

    private const MAX_MESSAGE_LENGTH = 2000;

    public function __construct(
        private Connection $connection,
        private ClockInterface $clock,
        private TransmissionStatusCatalog $catalog,
    ) {
    }

    public function open(InvoiceDocument $document): bool
    {
        if (false !== $this->connection->fetchOne('SELECT 1 FROM ' . self::TABLE . ' WHERE invoice_id = ?', [$document->invoiceId])) {
            return false;
        }

        $now = $this->clock->now();

        try {
            $this->connection->insert(self::TABLE, [
                'invoice_id' => $document->invoiceId,
                'invoice_number' => $document->invoiceNumber,
                'status' => TransmissionStatus::NOT_TRANSMITTED,
                'message' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ], [
                'created_at' => Types::DATETIME_IMMUTABLE,
                'updated_at' => Types::DATETIME_IMMUTABLE,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another process claimed the invoice between the check and the insert.
            return false;
        }

        return true;
    }

    public function record(string $invoiceId, string $status, ?string $message): void
    {
        $this->catalog->assertKnown($status);

        $this->connection->update(self::TABLE, [
            'status' => $status,
            'message' => null === $message ? null : mb_substr($message, 0, self::MAX_MESSAGE_LENGTH),
            'updated_at' => $this->clock->now(),
        ], ['invoice_id' => $invoiceId], [
            'updated_at' => Types::DATETIME_IMMUTABLE,
        ]);
    }
}
