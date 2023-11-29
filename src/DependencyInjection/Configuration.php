<?php

namespace Hgabka\GoogleCloudBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('hgabka_google_cloud');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('bucket')->isRequired()->cannotBeEmpty()->end()
            ->end()
            ->children()
                ->scalarNode('cache_path')->defaultValue('uploads/cache')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
