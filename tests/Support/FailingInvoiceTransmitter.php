<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmitterInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\TransmissionResult;

/** An adapter whose platform is down: it always throws. */
final class FailingInvoiceTransmitter implements InvoiceTransmitterInterface
{
    public function __construct(private readonly \Throwable $failure)
    {
    }

    public function transmit(InvoiceDocument $document): TransmissionResult
    {
        throw $this->failure;
    }
}
