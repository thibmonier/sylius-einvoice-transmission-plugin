<?php

declare(strict_types=1);

namespace Tmonier\SyliusEinvoiceTransmissionPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Tmonier\SyliusEinvoiceTransmissionPlugin\Transmission\NullInvoiceTransmitter;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('tmonier_sylius_einvoice_transmission');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('transmitter')
                    ->info('Id of the service that implements InvoiceTransmitterInterface. The default adapter sends nothing and logs the invoices as not transmitted.')
                    ->defaultValue(NullInvoiceTransmitter::class)
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('document_provider')
                    ->info('Id of the service that implements InvoiceDocumentProviderInterface. Without it, the relaunch of a send in error is not available.')
                    ->defaultNull()
                ->end()
                ->arrayNode('additional_statuses')
                    ->info('Statuses an adapter may answer in addition to deposited, rejected, error and not_transmitted (lower snake case).')
                    ->scalarPrototype()
                        ->validate()
                            ->ifTrue(static fn (mixed $status): bool => !\is_string($status) || 1 !== preg_match('/^[a-z][a-z0-9_]{0,63}$/', $status))
                            ->thenInvalid('An additional status is a lower snake case identifier of 64 characters at most, got %s.')
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
