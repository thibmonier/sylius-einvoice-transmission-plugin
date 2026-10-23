<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\Entity;

/**
 * One line of the log: read model of the journal. The lines are written by the journal with DBAL
 * (see DbalTransmissionJournal); the invoice id is the primary key, so an invoice has one line.
 */
class InvoiceTransmission
{
    private string $invoiceId;

    private string $invoiceNumber;

    private string $status;

    private ?string $message = null;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public function invoiceId(): string
    {
        return $this->invoiceId;
    }

    public function invoiceNumber(): string
    {
        return $this->invoiceNumber;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
