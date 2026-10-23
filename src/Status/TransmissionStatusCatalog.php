<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Status;

/** The statuses a send can have: the built-in ones, plus those the application declares. */
final class TransmissionStatusCatalog
{
    /** @var list<string> */
    private readonly array $statuses;

    /** @param list<string> $additional */
    public function __construct(array $additional = [])
    {
        $this->statuses = array_values(array_unique([...TransmissionStatus::BUILT_IN, ...$additional]));
    }

    /** @return list<string> */
    public function all(): array
    {
        return $this->statuses;
    }

    public function isKnown(string $status): bool
    {
        return \in_array($status, $this->statuses, true);
    }

    /** @throws UnknownTransmissionStatus */
    public function assertKnown(string $status): void
    {
        if (!$this->isKnown($status)) {
            throw UnknownTransmissionStatus::forStatus($status, $this->statuses);
        }
    }
}
