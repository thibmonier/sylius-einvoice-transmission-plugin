<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Tmonier\SyliusEinvoiceTransmissionPlugin\EventListener\InvoiceGeneratedListener;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\DbalTransmissionJournal;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\TransmissionJournalInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Status\TransmissionStatusCatalog;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmissionHandler;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\NullInvoiceTransmitter;
use Tmonier\SyliusFacturXPlugin\Event\FacturXInvoiceGenerated;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // The transmitter is the alias `tmonier_einvoice.transmitter`, set by the extension from the configuration.
    $services->set(NullInvoiceTransmitter::class);

    $services
        ->set(TransmissionStatusCatalog::class)
        ->args([param('tmonier_einvoice.additional_statuses')])
    ;

    $services
        ->set(DbalTransmissionJournal::class)
        ->args([
            service('doctrine.dbal.default_connection'),
            service('clock'),
            service(TransmissionStatusCatalog::class),
        ])
    ;
    $services->alias(TransmissionJournalInterface::class, DbalTransmissionJournal::class);

    $services
        ->set(InvoiceTransmissionHandler::class)
        ->args([
            service('tmonier_einvoice.transmitter'),
            service(TransmissionJournalInterface::class),
            service('logger'),
        ])
    ;

    $services
        ->set(InvoiceGeneratedListener::class)
        ->args([
            service(InvoiceTransmissionHandler::class),
            service('logger'),
        ])
        ->tag('kernel.event_listener', ['event' => FacturXInvoiceGenerated::NAME, 'method' => '__invoke'])
    ;
};
