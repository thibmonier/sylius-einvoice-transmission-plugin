<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;

/** The default adapter: nothing leaves the shop, the invoice is only logged as not transmitted. */
final class NullInvoiceTransmitter implements InvoiceTransmitterInterface
{
    public function transmit(InvoiceDocument $document): TransmissionResult
    {
        return new TransmissionResult(TransmissionStatus::NOT_TRANSMITTED, 'No transmitter configured.');
    }
}
