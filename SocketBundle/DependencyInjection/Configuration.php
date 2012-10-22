<?php

namespace MEF\SocketBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('mef_socket');
        
        $rootNode
            ->children()
                ->scalarNode('port')->end()
                ->scalarNode('host')->end()
                ->arrayNode('tcp')
                    ->children()
                        ->scalarNode('port')->isRequired()->end()
                        ->scalarNode('host')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('web')
                    ->children()
                        ->scalarNode('port')->isRequired()->end()
                        ->scalarNode('host')->isRequired()->end()
                    ->end()
                ->end()
                ->arrayNode('clients')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->canBeUnset()
                        ->children()
                            ->scalarNode('port')->isRequired()->end()
                            ->scalarNode('host')->isRequired()->end()
                            ->scalarNode('protocol')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('servers')
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                        ->canBeUnset()
                        ->children()
                            ->scalarNode('port')->isRequired()->end()
                            ->scalarNode('host')->isRequired()->end()
                            ->scalarNode('protocol')->isRequired()->end()
                        ->end()
                    ->end()
            ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
