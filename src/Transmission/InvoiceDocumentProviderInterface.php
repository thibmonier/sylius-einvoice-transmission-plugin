<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

/**
 * The port towards the application for a relaunch: the journal does not keep the generated invoice, so the application
 * gives it back (its XML and its PDF) from the invoice identifier.
 */
interface InvoiceDocumentProviderInterface
{
    /** @throws \RuntimeException when the document of the invoice cannot be found */
    public function provide(string $invoiceId): InvoiceDocument;
}
