<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

/** The answer of an adapter. The status must be one of the catalog, otherwise the journal refuses it. */
final class TransmissionResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $message = null,
    ) {
    }
}
