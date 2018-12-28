<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This class that loads and manages the Kimai configuration and container parameter.
 */
class KimaiClockInExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container)
    {
//        $configuration = new Configuration();
//        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');



//        $container->loadFromExtension('twig', array(
//            'paths' => array(
//                '' => 'KimaiClockInBundle',
//            ),
//        ));


//        $configuration = new Configuration();
//        try {
//            $config = $this->processConfiguration($configuration, $configs);
//        } catch (InvalidConfigurationException $e) {
//            trigger_error('Found invalid "kimai" configuration: ' . $e->getMessage());
//            $config = [];
//        }
//
//        $container->setParameter('kimai.languages', $config['languages']);
//        $container->setParameter('kimai.calendar', $config['calendar']);
//        $container->setParameter('kimai.dashboard', $config['dashboard']);
//        $container->setParameter('kimai.widgets', $config['widgets']);
//        $container->setParameter('kimai.invoice.documents', $config['invoice']['documents']);
//        $container->setParameter('kimai.defaults', $config['defaults']);
//
//        $this->createThemeParameter($config['theme'], $container);
//        $this->createUserParameter($config['user'], $container);
//        $this->createTimesheetParameter($config['timesheet'], $container);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function createThemeParameter(array $config, ContainerBuilder $container)
    {
        $container->setParameter('kimai.theme', $config);
        $container->setParameter('kimai.theme.select_type', $config['select_type']);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function createTimesheetParameter(array $config, ContainerBuilder $container)
    {
        $container->setParameter('kimai.timesheet.rates', $config['rates']);
        $container->setParameter('kimai.timesheet.rounding', $config['rounding']);
        $container->setParameter('kimai.timesheet.duration_only', $config['duration_only']);
        $container->setParameter('kimai.timesheet.markdown', $config['markdown_content']);
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return 'kimai_clock_in';
    }

    /**
     * Allow an extension to prepend the extension configurations.
     */
    public function prepend(ContainerBuilder $container)
    {
    }
}
