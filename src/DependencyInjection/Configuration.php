<?php

namespace Wizart\Tech\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration implements ConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getConfigTreeBuilder() : TreeBuilder
    {
        $this->treeBuilder = new TreeBuilder('wizart');

        $rootNode = $this->treeBuilder->getRootNode();
        $this->addGlobalOptionsSection($rootNode);

        return $this->treeBuilder;
    }

    private $treeBuilder;
    public function getTreeBuilder() : TreeBuilder { return $this->treeBuilder; }

    private function addGlobalOptionsSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->booleanNode('enable')
                    ->info('Enable feature')
                    ->defaultValue(True)
                ->end()
                ->booleanNode('autoappend')
                    ->info('Auto-append required dependencies into HTML page')
                    ->defaultValue(True)
                    ->end()
                ->scalarNode('token')
                    ->info('Wizart API token')
                    ->defaultValue('')
                ->end()
            ->end()
        ;
    }
}