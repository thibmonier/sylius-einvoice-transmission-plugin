<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Integration;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Support\TransmissionTestTrait;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Entity\InvoiceTransmission;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\DbalTransmissionJournal;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatusCatalog;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\UnknownTransmissionStatus;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceDocument;

/** The table built by the migration, and the guarantees the idempotence relies on. */
final class TransmissionJournalTest extends KernelTestCase
{
    use TransmissionTestTrait;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection()->beginTransaction();
    }

    public function testTheMigrationBuildsExactlyTheMappedSchema(): void
    {
        $entityManager = $this->entityManager();
        $statements = (new SchemaTool($entityManager))->getUpdateSchemaSql([$entityManager->getClassMetadata(InvoiceTransmission::class)]);

        $own = array_filter($statements, static fn (string $sql): bool => str_contains($sql, 'tmonier_einvoice_transmission') && 1 !== preg_match('/\bDROP\b/', $sql));
        self::assertSame([], array_values($own));
    }

    public function testTheTableHasTheCollationOfTheSyliusTablesSoThatItCanBeJoinedToThem(): void
    {
        if (!$this->connection()->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform) {
            self::markTestSkipped('Collations are a MySQL and MariaDB matter.');
        }

        $collation = $this->connection()->fetchOne(
            'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['tmonier_einvoice_transmission'],
        );
        $syliusCollation = $this->connection()->fetchOne(
            'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['sylius_order'],
        );

        self::assertSame($syliusCollation, $collation);
    }

    public function testTheDatabaseRefusesASecondLineForTheSameInvoice(): void
    {
        $this->journal()->open($this->document('invoice-1'));

        $this->expectException(UniqueConstraintViolationException::class);

        $this->connection()->insert('tmonier_einvoice_transmission', [
            'invoice_id' => 'invoice-1',
            'invoice_number' => 'FA-2',
            'status' => 'error',
            'created_at' => '2026-10-24 10:00:00',
            'updated_at' => '2026-10-24 10:00:00',
        ]);
    }

    public function testAnInvoiceAlreadyLoggedIsNotClaimedAgain(): void
    {
        $journal = $this->journal();

        self::assertTrue($journal->open($this->document('invoice-1')));
        self::assertFalse($journal->open($this->document('invoice-1')));
    }

    public function testAnUnknownStatusWritesNothing(): void
    {
        $journal = $this->journal();
        $journal->open($this->document('invoice-1'));

        try {
            $journal->record('invoice-1', 'paid', 'x');
            self::fail('The status should be refused.');
        } catch (UnknownTransmissionStatus) {
            $log = $this->logged('invoice-1');
            self::assertNotNull($log);
            self::assertSame('not_transmitted', $log->status());
        }
    }

    public function testAnOverlongMessageIsCut(): void
    {
        $journal = $this->journal();
        $journal->open($this->document('invoice-1'));

        $journal->record('invoice-1', 'error', str_repeat('é', 5000));

        $log = $this->logged('invoice-1');
        self::assertNotNull($log);
        self::assertSame(2000, mb_strlen((string) $log->message()));
    }

    private function journal(): DbalTransmissionJournal
    {
        return new DbalTransmissionJournal($this->connection(), new MockClock('2026-10-24 10:00:00'), new TransmissionStatusCatalog());
    }

    private function document(string $invoiceId): InvoiceDocument
    {
        return new InvoiceDocument($invoiceId, 'FA-1', '<xml/>', 'pdf', 'FA-1.pdf');
    }
}
