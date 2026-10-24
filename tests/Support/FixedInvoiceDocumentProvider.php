<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support;

use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocumentProviderInterface;

/** Gives back the document of any invoice and counts the calls. */
final class FixedInvoiceDocumentProvider implements InvoiceDocumentProviderInterface
{
    public int $calls = 0;

    public function provide(string $invoiceId): InvoiceDocument
    {
        ++$this->calls;

        return new InvoiceDocument($invoiceId, 'FA-' . $invoiceId, '<xml/>', 'pdf', 'FA-' . $invoiceId . '.pdf');
    }
}
