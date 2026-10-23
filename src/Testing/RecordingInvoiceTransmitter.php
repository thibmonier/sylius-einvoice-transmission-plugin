<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Testing;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmitterInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\TransmissionResult;

/** A test double for applications and adapters: keeps what it receives, answers a fixed result. */
final class RecordingInvoiceTransmitter implements InvoiceTransmitterInterface
{
    /** @var list<InvoiceDocument> */
    private array $transmitted = [];

    public function __construct(private readonly TransmissionResult $result = new TransmissionResult(TransmissionStatus::DEPOSITED))
    {
    }

    public function transmit(InvoiceDocument $document): TransmissionResult
    {
        $this->transmitted[] = $document;

        return $this->result;
    }

    /** @return list<InvoiceDocument> */
    public function transmitted(): array
    {
        return $this->transmitted;
    }
}
