<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support\FailingInvoiceTransmitter;
use Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support\FixedStatusInvoiceTransmitter;
use Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support\TransmissionTestTrait;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Testing\RecordingInvoiceTransmitter;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\NullInvoiceTransmitter;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\TransmissionResult;
use Tmonier\SyliusFacturXPlugin\Event\FacturXInvoiceGenerated;

/**
 * The five scenarios of US-180, against the real database (one test per scenario, in the order of the story).
 */
final class TransmissionScenariosTest extends KernelTestCase
{
    use TransmissionTestTrait;

    private MockClock $clock;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection()->beginTransaction();
        $this->clock = new MockClock('2026-10-24 10:00:00');
    }

    /** Scenario: Facture transmise et journalisée (nominal) */
    public function testAnInvoiceIsTransmittedOnceAndTheSendIsLogged(): void
    {
        $transmitter = new RecordingInvoiceTransmitter(new TransmissionResult(TransmissionStatus::DEPOSITED, 'Accepted for delivery'));

        $this->listenerFor($transmitter, $this->clock)($this->generated('invoice-1', 'FA-2026-0001'));

        self::assertCount(1, $transmitter->transmitted());
        self::assertSame('invoice-1', $transmitter->transmitted()[0]->invoiceId);
        self::assertSame('<rsm:CrossIndustryInvoice/>', $transmitter->transmitted()[0]->xml);
        $log = $this->logged('invoice-1');
        self::assertNotNull($log);
        self::assertSame('FA-2026-0001', $log->invoiceNumber());
        self::assertSame(TransmissionStatus::DEPOSITED, $log->status());
        self::assertSame('Accepted for delivery', $log->message());
        self::assertSame('2026-10-24 10:00:00', $log->updatedAt()->format('Y-m-d H:i:s'));
    }

    /** Scenario: Événement rejoué (alternatif) */
    public function testAReplayedEventDoesNotSendTheInvoiceAgain(): void
    {
        $transmitter = new RecordingInvoiceTransmitter();
        $listener = $this->listenerFor($transmitter, $this->clock);

        $listener($this->generated('invoice-1'));
        $this->clock->sleep(3600);
        $listener($this->generated('invoice-1'));

        self::assertCount(1, $transmitter->transmitted());
        self::assertSame(1, $this->loggedCount());
        $log = $this->logged('invoice-1');
        self::assertNotNull($log);
        self::assertSame('2026-10-24 10:00:00', $log->updatedAt()->format('Y-m-d H:i:s'));
    }

    /** Scenario: Aucun adaptateur configuré (alternatif) */
    public function testTheDefaultConfigurationLogsTheInvoiceAsNotTransmittedWithTheNullAdapter(): void
    {
        self::assertInstanceOf(NullInvoiceTransmitter::class, self::getContainer()->get('test.tmonier_einvoice.transmitter'));
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch($this->generated('invoice-default'), FacturXInvoiceGenerated::NAME);

        $log = $this->logged('invoice-default');
        self::assertNotNull($log);
        self::assertSame(TransmissionStatus::NOT_TRANSMITTED, $log->status());
    }

    /** Scenario: Adaptateur en échec (erreur) */
    public function testAFailingAdapterIsLoggedAsAnErrorAndDoesNotBreakTheGeneration(): void
    {
        $listener = $this->listenerFor(new FailingInvoiceTransmitter(new \RuntimeException('Platform unreachable')), $this->clock);

        $listener($this->generated('invoice-1'));

        $log = $this->logged('invoice-1');
        self::assertNotNull($log);
        self::assertSame(TransmissionStatus::ERROR, $log->status());
        self::assertSame('Platform unreachable', $log->message());
    }

    /** Scenario: Statut inconnu (erreur) */
    public function testAnUnknownStatusIsRefusedWithAClearMessageAndNeverStored(): void
    {
        $listener = $this->listenerFor(new FixedStatusInvoiceTransmitter('magically_accepted'), $this->clock);

        $listener($this->generated('invoice-1'));

        $log = $this->logged('invoice-1');
        self::assertNotNull($log);
        self::assertSame(TransmissionStatus::ERROR, $log->status());
        self::assertStringContainsString('Unknown transmission status "magically_accepted"', (string) $log->message());
        self::assertSame([], $this->connection()->fetchAllAssociative('SELECT invoice_id FROM tmonier_einvoice_transmission WHERE status = ?', ['magically_accepted']));
    }
}
