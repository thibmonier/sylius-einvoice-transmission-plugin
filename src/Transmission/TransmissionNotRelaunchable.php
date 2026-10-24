<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;

/** Only a send in error is relaunched: a deposited or rejected invoice is not sent a second time. */
final class TransmissionNotRelaunchable extends \DomainException
{
    public static function forInvoice(string $invoiceId): self
    {
        return new self(sprintf('The transmission of the invoice "%s" cannot be relaunched: only a send with the status "%s" can.', $invoiceId, TransmissionStatus::ERROR));
    }
}
