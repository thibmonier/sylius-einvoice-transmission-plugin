<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

/** What an adapter receives: the generated Factur-X invoice, without any Sylius entity. */
final class InvoiceDocument
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $invoiceNumber,
        public readonly string $xml,
        public readonly string $pdfContent,
        public readonly string $pdfFilename,
    ) {
    }
}
