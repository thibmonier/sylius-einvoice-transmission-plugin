<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

use Psr\Log\LoggerInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\TransmissionJournalInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\UnknownTransmissionStatus;

/**
 * Sends an invoice once: the journal claims it first, so a replayed event finds the line and stops.
 * An invoice claimed and not answered (the process died) stays "not transmitted": it is never
 * sent a second time by a replay.
 */
final readonly class InvoiceTransmissionHandler
{
    public function __construct(
        private InvoiceTransmitterInterface $transmitter,
        private TransmissionJournalInterface $journal,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(InvoiceDocument $document): void
    {
        if (!$this->journal->open($document)) {
            return;
        }

        try {
            $result = $this->transmitter->transmit($document);
        } catch (\Throwable $exception) {
            $this->logger->warning('Invoice transmission failed.', ['invoice' => $document->invoiceId, 'exception' => $exception::class]);
            $this->journal->record($document->invoiceId, TransmissionStatus::ERROR, $exception->getMessage());

            return;
        }

        try {
            $this->journal->record($document->invoiceId, $result->status, $result->message);
        } catch (UnknownTransmissionStatus $exception) {
            $this->logger->warning('Invoice transmission answered an unknown status.', ['invoice' => $document->invoiceId]);
            $this->journal->record($document->invoiceId, TransmissionStatus::ERROR, $exception->getMessage());
        }
    }
}
