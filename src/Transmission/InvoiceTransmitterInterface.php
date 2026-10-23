<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

/**
 * The port towards a platform. An adapter sends the invoice and answers a status; it may throw,
 * the exception is logged as an error and never reaches the generation of the invoice.
 */
interface InvoiceTransmitterInterface
{
    public function transmit(InvoiceDocument $document): TransmissionResult;
}
