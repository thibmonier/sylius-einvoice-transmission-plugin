<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Journal;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\UnknownTransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;

/** The log of the sends: one line per invoice. */
interface TransmissionJournalInterface
{
    /**
     * Claims the invoice: false when it is already logged, so that it is never sent twice.
     * The line starts as "not transmitted" until the adapter has answered.
     */
    public function open(InvoiceDocument $document): bool;

    /**
     * Opens again the line of an invoice whose send ended in error, as "not transmitted", and keeps the line (its creation date
     * included). False for any other status or an unknown invoice, and for the loser of two reopenings at once.
     */
    public function reopen(string $invoiceId): bool;

    /** @throws UnknownTransmissionStatus when the status is not in the catalog (nothing is written) */
    public function record(string $invoiceId, string $status, ?string $message): void;
}
