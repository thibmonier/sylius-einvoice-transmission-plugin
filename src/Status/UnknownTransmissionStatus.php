<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Status;

final class UnknownTransmissionStatus extends \InvalidArgumentException
{
    private const MAX_SHOWN_LENGTH = 40;

    /** @param list<string> $known */
    public static function forStatus(string $status, array $known): self
    {
        // The status comes from an adapter: it is cut so that it cannot flood the log.
        $shown = mb_strlen($status) > self::MAX_SHOWN_LENGTH ? mb_substr($status, 0, self::MAX_SHOWN_LENGTH) . '...' : $status;

        return new self(sprintf('Unknown transmission status "%s". Known statuses: %s.', $shown, implode(', ', $known)));
    }
}
