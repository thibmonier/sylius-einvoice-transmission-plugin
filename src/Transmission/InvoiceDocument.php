<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

/** What an adapter receives: the generated Factur-X invoice, without any Sylius entity. */
final readonly class InvoiceDocument
{
    public function __construct(
        public string $invoiceId,
        public string $invoiceNumber,
        public string $xml,
        public string $pdfContent,
        public string $pdfFilename,
    ) {
    }
}
