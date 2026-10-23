<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Unit\Status;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatusCatalog;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\UnknownTransmissionStatus;

final class TransmissionStatusCatalogTest extends TestCase
{
    public function testTheMinimalSetIsDepositedRejectedErrorAndNotTransmitted(): void
    {
        self::assertSame(['deposited', 'rejected', 'error', 'not_transmitted'], (new TransmissionStatusCatalog())->all());
    }

    public function testAnAdditionalStatusExtendsTheSet(): void
    {
        $catalog = new TransmissionStatusCatalog(['received']);

        self::assertTrue($catalog->isKnown('received'));
        self::assertTrue($catalog->isKnown(TransmissionStatus::DEPOSITED));
    }

    public function testAnUnknownStatusIsRefusedWithTheKnownOnesInTheMessage(): void
    {
        $this->expectException(UnknownTransmissionStatus::class);
        $this->expectExceptionMessage('Unknown transmission status "paid". Known statuses: deposited, rejected, error, not_transmitted.');

        (new TransmissionStatusCatalog())->assertKnown('paid');
    }

    public function testAnOverlongStatusIsCutInTheMessage(): void
    {
        try {
            (new TransmissionStatusCatalog())->assertKnown(str_repeat('x', 500));
            self::fail('The status should be refused.');
        } catch (UnknownTransmissionStatus $exception) {
            self::assertLessThan(200, \strlen($exception->getMessage()));
        }
    }
}
