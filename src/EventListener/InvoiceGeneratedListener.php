<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\EventListener;

use Psr\Log\LoggerInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmissionHandler;
use Tmonier\SyliusFacturXPlugin\Event\FacturXInvoiceGenerated;

/** Listens to `tmonier_facturx.invoice_generated`. Whatever happens, the generation of the invoice goes on. */
final readonly class InvoiceGeneratedListener
{
    public function __construct(
        private InvoiceTransmissionHandler $handler,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(FacturXInvoiceGenerated $event): void
    {
        $invoice = $event->getInvoice();

        try {
            $this->handler->handle(new InvoiceDocument(
                $invoice->id(),
                $invoice->number(),
                $event->getXml(),
                $event->getPdfContent(),
                $event->getPdfFilename(),
            ));
        } catch (\Throwable $exception) {
            $this->logger->error('Invoice transmission could not be handled.', ['invoice' => $invoice->id(), 'exception' => $exception::class]);
        }
    }
}
