<?php

/*
 * This file is part of the Kimai Clock-In bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LDuer\KimaiClockInBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class validates and merges configuration from the files:
 * - config/packages/kimai.yaml
 * - config/packages/local.yaml
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('kimai_clock_in');

        $rootNode
            ->children()
//                ->append($this->getUserNode())
//                ->append($this->getTimesheetNode())
            ->end()
        ->end();

        return $treeBuilder;
    }

    protected function getTimesheetNode()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('duer_timetracking');

//        $node
//            ->children()
//                ->booleanNode('duration_only')
//                    ->defaultValue(false)
//                ->end()
//                ->booleanNode('markdown_content')
//                    ->defaultValue(false)
//                ->end()
//                ->arrayNode('rounding')
//                    ->requiresAtLeastOneElement()
//                    ->useAttributeAsKey('key')
//                    ->arrayPrototype()
//                        ->children()
//                            ->arrayNode('days')
//                                ->requiresAtLeastOneElement()
//                                ->useAttributeAsKey('key')
//                                ->isRequired()
//                                ->prototype('scalar')->end()
//                                ->defaultValue([])
//                            ->end()
//                            ->integerNode('begin')
//                                ->defaultValue(0)
//                            ->end()
//                            ->integerNode('end')
//                                ->defaultValue(0)
//                            ->end()
//                            ->integerNode('duration')
//                                ->defaultValue(0)
//                            ->end()
//                        ->end()
//                    ->end()
//                    ->defaultValue([])
//                ->end()
//
//                ->arrayNode('rates')
//                    ->requiresAtLeastOneElement()
//                    ->useAttributeAsKey('key')
//                    ->arrayPrototype()
//                        ->children()
//                            ->arrayNode('days')
//                                ->requiresAtLeastOneElement()
//                                ->useAttributeAsKey('key')
//                                ->isRequired()
//                                ->prototype('scalar')->end()
//                                ->defaultValue([])
//                            ->end()
//                            ->floatNode('factor')
//                                ->isRequired()
//                                ->defaultValue(1)
//                                ->validate()
//                                    ->ifTrue(function ($value) {
//                                        return $value <= 0;
//                                    })
//                                    ->thenInvalid('A rate factor smaller or equals 0 is not allowed')
//                                ->end()
//                            ->end()
//                        ->end()
//                    ->end()
//                    ->defaultValue([])
//                ->end()
//            ->end()

        return $node;
    }
}
