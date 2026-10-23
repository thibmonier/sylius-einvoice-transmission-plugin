<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Sylius\InvoicingPlugin\Entity\InvoiceInterface;
use Symfony\Component\Clock\ClockInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Entity\InvoiceTransmission;
use Tmonier\SyliusEinvoiceTransmissionPlugin\EventListener\InvoiceGeneratedListener;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\DbalTransmissionJournal;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatusCatalog;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmissionHandler;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmitterInterface;
use Tmonier\SyliusFacturXPlugin\Event\FacturXInvoiceGenerated;

/** For the kernel tests: the listener built around a given adapter, on the real database. */
trait TransmissionTestTrait
{
    private function connection(): Connection
    {
        /** @var \Doctrine\Bundle\DoctrineBundle\Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');

        return $doctrine->getConnection();
    }

    private function entityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');

        return $entityManager;
    }

    private function listenerFor(InvoiceTransmitterInterface $transmitter, ClockInterface $clock): InvoiceGeneratedListener
    {
        $journal = new DbalTransmissionJournal($this->connection(), $clock, new TransmissionStatusCatalog());

        return new InvoiceGeneratedListener(
            new InvoiceTransmissionHandler($transmitter, $journal, new NullLogger()),
            new NullLogger(),
        );
    }

    private function generated(string $invoiceId = 'invoice-1', string $number = 'FA-2026-0001'): FacturXInvoiceGenerated
    {
        $invoice = $this->createStub(InvoiceInterface::class);
        $invoice->method('id')->willReturn($invoiceId);
        $invoice->method('number')->willReturn($number);

        return new FacturXInvoiceGenerated($invoice, '<rsm:CrossIndustryInvoice/>', '%PDF-1.7 test', $number . '.pdf');
    }

    private function logged(string $invoiceId): ?InvoiceTransmission
    {
        $this->entityManager()->clear();

        return $this->entityManager()->find(InvoiceTransmission::class, $invoiceId);
    }

    private function loggedCount(): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM tmonier_einvoice_transmission');
    }
}
