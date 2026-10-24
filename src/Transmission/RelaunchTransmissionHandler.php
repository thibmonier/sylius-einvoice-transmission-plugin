<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\TransmissionJournalInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;

/**
 * Sends again an invoice whose send ended in error. The journal reopens the line first, and only for the status "error":
 * two relaunches at once, or a relaunch of an invoice already deposited, find the line closed and send nothing.
 */
final readonly class RelaunchTransmissionHandler
{
    public function __construct(
        private InvoiceTransmissionHandler $handler,
        private TransmissionJournalInterface $journal,
        private InvoiceDocumentProviderInterface $documents,
    ) {
    }

    /** @throws TransmissionNotRelaunchable when the invoice is unknown or its send is not in error */
    public function relaunch(string $invoiceId): void
    {
        if (!$this->journal->reopen($invoiceId)) {
            throw TransmissionNotRelaunchable::forInvoice($invoiceId);
        }

        try {
            $document = $this->documents->provide($invoiceId);
        } catch (\Throwable $exception) {
            // The line was reopened: it goes back to "error", otherwise it would stay "not transmitted" and never be relaunched.
            $this->journal->record($invoiceId, TransmissionStatus::ERROR, $exception->getMessage());

            throw $exception;
        }

        $this->handler->deliver($document);
    }
}
