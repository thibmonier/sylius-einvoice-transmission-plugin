<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\TransmissionJournalInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatusCatalog;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;

/** The contract of the journal without a database: one line per invoice, statuses checked by the catalog. */
final class InMemoryTransmissionJournal implements TransmissionJournalInterface
{
    /** @var array<string, array{status: string, message: ?string}> */
    public array $lines = [];

    public function __construct(private readonly TransmissionStatusCatalog $catalog = new TransmissionStatusCatalog())
    {
    }

    public function open(InvoiceDocument $document): bool
    {
        if (isset($this->lines[$document->invoiceId])) {
            return false;
        }
        $this->lines[$document->invoiceId] = ['status' => TransmissionStatus::NOT_TRANSMITTED, 'message' => null];

        return true;
    }

    public function record(string $invoiceId, string $status, ?string $message): void
    {
        $this->catalog->assertKnown($status);
        $this->lines[$invoiceId] = ['status' => $status, 'message' => $message];
    }

    public function reopen(string $invoiceId): bool
    {
        if (TransmissionStatus::ERROR !== ($this->lines[$invoiceId]['status'] ?? null)) {
            return false;
        }
        $this->lines[$invoiceId] = ['status' => TransmissionStatus::NOT_TRANSMITTED, 'message' => null];

        return true;
    }

    public function statusOf(string $invoiceId): ?string
    {
        return $this->lines[$invoiceId]['status'] ?? null;
    }
}
