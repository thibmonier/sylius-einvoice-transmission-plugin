<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusEinvoiceTransmissionPlugin\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tmonier\SyliusEinvoiceTransmissionPlugin\DependencyInjection\TmonierSyliusEinvoiceTransmissionExtension;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\NullInvoiceTransmitter;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\RelaunchTransmissionHandler;

final class TmonierSyliusEinvoiceTransmissionExtensionTest extends TestCase
{
    public function testTheNullAdapterIsTheDefaultTransmitter(): void
    {
        $container = $this->loaded([]);

        self::assertSame(NullInvoiceTransmitter::class, (string) $container->getAlias('tmonier_einvoice.transmitter'));
    }

    public function testTheTransmitterIsChosenByServiceId(): void
    {
        $container = $this->loaded(['transmitter' => 'app.my_transmitter']);

        self::assertSame('app.my_transmitter', (string) $container->getAlias('tmonier_einvoice.transmitter'));
    }

    public function testAdditionalStatusesAreAvailableToTheCatalog(): void
    {
        $container = $this->loaded(['additional_statuses' => ['received']]);

        self::assertSame(['received'], $container->getParameter('tmonier_einvoice.additional_statuses'));
    }

    public function testAnAdditionalStatusMustBeALowerSnakeCaseIdentifier(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->loaded(['additional_statuses' => ['Not Valid!']]);
    }

    public function testTheRelaunchIsOnlyAvailableOnceTheApplicationGivesItsDocumentProvider(): void
    {
        self::assertFalse($this->loaded([])->hasDefinition(RelaunchTransmissionHandler::class));

        $container = $this->loaded(['document_provider' => 'app.invoice_documents']);

        self::assertTrue($container->hasDefinition(RelaunchTransmissionHandler::class));
        self::assertSame('app.invoice_documents', (string) $container->getAlias('tmonier_einvoice.document_provider'));
    }

    /** @param array<string, mixed> $config */
    private function loaded(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new TmonierSyliusEinvoiceTransmissionExtension())->load([$config], $container);

        return $container;
    }
}
