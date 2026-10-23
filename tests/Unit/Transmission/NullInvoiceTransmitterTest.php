<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Unit\Transmission;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\NullInvoiceTransmitter;

final class NullInvoiceTransmitterTest extends TestCase
{
    public function testTheNullAdapterReportsTheInvoiceAsNotTransmitted(): void
    {
        $result = (new NullInvoiceTransmitter())->transmit(new InvoiceDocument('invoice-1', 'FA-2026-0001', '<xml/>', 'pdf', 'FA-2026-0001.pdf'));

        self::assertSame(TransmissionStatus::NOT_TRANSMITTED, $result->status);
    }
}
