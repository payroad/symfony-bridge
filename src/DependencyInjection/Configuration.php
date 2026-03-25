<?php

declare(strict_types=1);

namespace Payroad\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('payroad');
        $root = $tree->getRootNode();

        $root
            ->children()
                ->arrayNode('providers')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('factory')
                                ->isRequired()
                                ->info('Fully-qualified class name of a ProviderFactoryInterface implementation.')
                            ->end()
                            ->arrayNode('config')
                                ->useAttributeAsKey('key')
                                ->scalarPrototype()->end()
                                ->info('Provider-specific config values. May reference env vars via %%env(VAR)%%.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $tree;
    }
}
