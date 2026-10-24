<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Unit\Transmission;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support\FixedInvoiceDocumentProvider;
use Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support\InMemoryTransmissionJournal;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Testing\RecordingInvoiceTransmitter;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocumentProviderInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmissionHandler;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmitterInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\RelaunchTransmissionHandler;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\TransmissionNotRelaunchable;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\TransmissionResult;

/** Only a send in error is relaunched, once: the adapter is called again, the line stays the same. */
final class RelaunchTransmissionHandlerTest extends TestCase
{
    public function testASendInErrorIsRelaunchedOnTheSameLineAndTheAdapterIsCalledOnce(): void
    {
        $journal = new InMemoryTransmissionJournal();
        $journal->lines['invoice-1'] = ['status' => 'error', 'message' => 'Platform unreachable'];
        $transmitter = new RecordingInvoiceTransmitter(new TransmissionResult('deposited'));
        $provider = new FixedInvoiceDocumentProvider();

        $this->handler($transmitter, $journal, $provider)->relaunch('invoice-1');

        self::assertCount(1, $transmitter->transmitted());
        self::assertSame('invoice-1', $transmitter->transmitted()[0]->invoiceId);
        self::assertSame(1, $provider->calls);
        self::assertSame(['invoice-1'], array_keys($journal->lines), 'no second line');
        self::assertSame(['status' => 'deposited', 'message' => null], $journal->lines['invoice-1']);
    }

    public function testARelaunchThatFailsAgainIsLoggedAsAnErrorAndCanBeRelaunchedAgain(): void
    {
        $journal = new InMemoryTransmissionJournal();
        $journal->lines['invoice-1'] = ['status' => 'error', 'message' => 'first failure'];
        $failing = new class() implements InvoiceTransmitterInterface {
            public function transmit(InvoiceDocument $document): TransmissionResult
            {
                throw new \RuntimeException('still down');
            }
        };

        $this->handler($failing, $journal, new FixedInvoiceDocumentProvider())->relaunch('invoice-1');

        self::assertSame(['status' => 'error', 'message' => 'still down'], $journal->lines['invoice-1']);
        self::assertTrue($journal->reopen('invoice-1'), 'an error stays relaunchable');
    }

    /** @return iterable<string, array{string}> */
    public static function notRelaunchable(): iterable
    {
        yield 'deposited' => ['deposited'];
        yield 'rejected' => ['rejected'];
        yield 'not transmitted' => ['not_transmitted'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('notRelaunchable')]
    public function testOnlyTheErrorStatusIsRelaunchedAndNothingIsSent(string $status): void
    {
        $journal = new InMemoryTransmissionJournal();
        $journal->lines['invoice-1'] = ['status' => $status, 'message' => null];
        $transmitter = new RecordingInvoiceTransmitter();

        try {
            $this->handler($transmitter, $journal, new FixedInvoiceDocumentProvider())->relaunch('invoice-1');
            self::fail('The relaunch should be refused.');
        } catch (TransmissionNotRelaunchable $exception) {
            self::assertStringContainsString('invoice-1', $exception->getMessage());
            self::assertStringContainsString('error', $exception->getMessage(), 'the message says which status can be relaunched');
        }

        self::assertSame([], $transmitter->transmitted());
        self::assertSame($status, $journal->statusOf('invoice-1'));
    }

    public function testAnInvoiceThatWasNeverSentIsRefused(): void
    {
        $transmitter = new RecordingInvoiceTransmitter();

        $this->expectException(TransmissionNotRelaunchable::class);

        try {
            $this->handler($transmitter, new InMemoryTransmissionJournal(), new FixedInvoiceDocumentProvider())->relaunch('unknown');
        } finally {
            self::assertSame([], $transmitter->transmitted());
        }
    }

    public function testASecondRelaunchAfterTheFirstOneSucceededSendsNothingMore(): void
    {
        $journal = new InMemoryTransmissionJournal();
        $journal->lines['invoice-1'] = ['status' => 'error', 'message' => 'x'];
        $transmitter = new RecordingInvoiceTransmitter(new TransmissionResult('deposited'));
        $handler = $this->handler($transmitter, $journal, new FixedInvoiceDocumentProvider());

        $handler->relaunch('invoice-1');
        try {
            $handler->relaunch('invoice-1');
            self::fail('The second relaunch should be refused.');
        } catch (TransmissionNotRelaunchable) {
        }

        self::assertCount(1, $transmitter->transmitted());
    }

    public function testADocumentThatCannotBeProvidedLeavesTheLineInErrorSoThatItCanBeRelaunchedAgain(): void
    {
        $journal = new InMemoryTransmissionJournal();
        $journal->lines['invoice-1'] = ['status' => 'error', 'message' => 'first failure'];
        $missing = new class() implements InvoiceDocumentProviderInterface {
            public function provide(string $invoiceId): InvoiceDocument
            {
                throw new \RuntimeException('The PDF of the invoice is gone.');
            }
        };
        $transmitter = new RecordingInvoiceTransmitter();
        $handler = new RelaunchTransmissionHandler(new InvoiceTransmissionHandler($transmitter, $journal, new NullLogger()), $journal, $missing);

        try {
            $handler->relaunch('invoice-1');
            self::fail('The missing document should be reported.');
        } catch (\RuntimeException $exception) {
            self::assertSame('The PDF of the invoice is gone.', $exception->getMessage());
        }

        self::assertSame(['status' => 'error', 'message' => 'The PDF of the invoice is gone.'], $journal->lines['invoice-1']);
        self::assertSame([], $transmitter->transmitted());
    }

    private function handler(InvoiceTransmitterInterface $transmitter, InMemoryTransmissionJournal $journal, FixedInvoiceDocumentProvider $provider): RelaunchTransmissionHandler
    {
        return new RelaunchTransmissionHandler(new InvoiceTransmissionHandler($transmitter, $journal, new NullLogger()), $journal, $provider);
    }
}
