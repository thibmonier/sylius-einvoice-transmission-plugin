<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Journal\TransmissionJournalInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\InvoiceTransmissionHandler;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\RelaunchTransmissionHandler;

final class TmonierSyliusEinvoiceTransmissionExtension extends Extension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function getAlias(): string
    {
        return 'tmonier_sylius_einvoice_transmission';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{transmitter: string, document_provider: ?string, additional_statuses: list<string>} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('tmonier_einvoice.additional_statuses', $config['additional_statuses']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.php');

        $container->setAlias('tmonier_einvoice.transmitter', $config['transmitter']);

        if (null !== $config['document_provider']) {
            $container->setAlias('tmonier_einvoice.document_provider', $config['document_provider']);
            $container->register(RelaunchTransmissionHandler::class, RelaunchTransmissionHandler::class)->setArguments([
                new Reference(InvoiceTransmissionHandler::class),
                new Reference(TransmissionJournalInterface::class),
                new Reference('tmonier_einvoice.document_provider'),
            ]);
        }
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'TmonierSyliusEinvoiceTransmissionPlugin' => [
                        'type' => 'xml',
                        'dir' => 'config/doctrine',
                        'prefix' => 'Tmonier\SyliusEinvoiceTransmissionPlugin\Entity',
                        'is_bundle' => true,
                    ],
                ],
            ],
        ]);

        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Tmonier\SyliusEinvoiceTransmissionPlugin\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@TmonierSyliusEinvoiceTransmissionPlugin/src/Migrations';
    }

    /** @return list<string> */
    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
