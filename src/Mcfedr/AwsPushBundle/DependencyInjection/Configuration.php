<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\DependencyInjection;

use Mcfedr\AwsPushBundle\Message\Message;
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
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('mcfedr_aws_push');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('mcfedr_aws_push');
        }

        $rootNode
            ->children()
                ->arrayNode('aws')
                    ->children()
                        ->variableNode('credentials')->end()
                        ->scalarNode('key')->cannotBeEmpty()->setDeprecated('The "%node%" option is deprecated. Use "credentials" instead.')->end()
                        ->scalarNode('secret')->cannotBeEmpty()->setDeprecated('The "%node%" option is deprecated. Use "credentials" instead.')->end()
                        ->scalarNode('region')->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->variableNode('platforms')->end()
                ->booleanNode('fcm')->end()
                ->arrayNode('pushPlatforms')->enumPrototype()->values(Message::ALL_PLATFORMS)->end()->end()
                ->booleanNode('debug')->defaultFalse()->end()
                ->scalarNode('topic_arn')->cannotBeEmpty()->end()
            ->end()
        ->end();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
