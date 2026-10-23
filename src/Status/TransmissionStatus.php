<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Status;

/**
 * The minimal set of statuses of a send. The official lifecycle statuses of the platforms are not
 * confirmed by a source read for this plugin (to be verified, see the README): the set is kept
 * small and an application extends it with the `additional_statuses` option.
 */
final class TransmissionStatus
{
    public const DEPOSITED = 'deposited';

    public const REJECTED = 'rejected';

    public const ERROR = 'error';

    public const NOT_TRANSMITTED = 'not_transmitted';

    /** @var list<string> */
    public const BUILT_IN = [self::DEPOSITED, self::REJECTED, self::ERROR, self::NOT_TRANSMITTED];

    private function __construct()
    {
    }
}
