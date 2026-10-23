<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission;

/** The answer of an adapter. The status must be one of the catalog, otherwise the journal refuses it. */
final readonly class TransmissionResult
{
    public function __construct(
        public string $status,
        public ?string $message = null,
    ) {
    }
}
