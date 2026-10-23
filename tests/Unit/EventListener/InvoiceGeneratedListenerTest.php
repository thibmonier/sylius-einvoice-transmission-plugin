<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sylius\InvoicingPlugin\Entity\InvoiceInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\EventListener\InvoiceGeneratedListener;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\TransmissionJournalInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Testing\RecordingInvoiceTransmitter;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmissionHandler;
use Tmonier\SyliusFacturXPlugin\Event\FacturXInvoiceGenerated;

final class InvoiceGeneratedListenerTest extends TestCase
{
    public function testAnUnreachableJournalDoesNotBreakTheGenerationOfTheInvoice(): void
    {
        $journal = new class() implements TransmissionJournalInterface {
            public function open(InvoiceDocument $document): bool
            {
                throw new \RuntimeException('Database is gone');
            }

            public function record(string $invoiceId, string $status, ?string $message): void
            {
                throw new \RuntimeException('Database is gone');
            }
        };
        $transmitter = new RecordingInvoiceTransmitter();
        $listener = new InvoiceGeneratedListener(new InvoiceTransmissionHandler($transmitter, $journal, new NullLogger()), new NullLogger());
        $invoice = $this->createStub(InvoiceInterface::class);
        $invoice->method('id')->willReturn('invoice-1');
        $invoice->method('number')->willReturn('FA-2026-0001');

        $listener(new FacturXInvoiceGenerated($invoice, '<xml/>', 'pdf', 'FA-2026-0001.pdf'));

        self::assertSame([], $transmitter->transmitted());
    }
}
