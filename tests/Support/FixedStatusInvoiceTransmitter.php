<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmitterInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\TransmissionResult;

/** An adapter that answers any status, known or not. */
final class FixedStatusInvoiceTransmitter implements InvoiceTransmitterInterface
{
    public function __construct(private readonly string $status)
    {
    }

    public function transmit(InvoiceDocument $document): TransmissionResult
    {
        return new TransmissionResult($this->status);
    }
}
