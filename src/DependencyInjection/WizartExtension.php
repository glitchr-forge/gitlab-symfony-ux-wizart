<?php

namespace Wizart\Tech\DependencyInjection;
use Wizart\Tech\Service\WizartService;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class WizartExtension extends Extension
{
    /**
     * {@inheritdoc}
     */

    public function load(array $configs, ContainerBuilder $container)
    {
        //
        // Load service declaration (includes services, controllers,..)
        dump("heho");
        
        // Format XML
        $loader = new XmlFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.xml');

        //
        // Configuration file: ./config/package/Wizart_bundle.yaml
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);
        $this->setConfiguration($container, $config, $configuration->getTreeBuilder()->getRootNode()->getNode()->getName());

        //
        // Alias declaration
        $container->setAlias(WizartService::class, 'wizart.service')->setPublic(true);
    }

    public function setConfiguration(ContainerBuilder $container, array $config, $globalKey = "")
    {
        foreach($config as $key => $value) {

            if (!empty($globalKey)) $key = $globalKey.".".$key;

            if (is_array($value)) $this->setConfiguration($container, $value, $key);
            else $container->setParameter($key, $value);
        }
    }
}
