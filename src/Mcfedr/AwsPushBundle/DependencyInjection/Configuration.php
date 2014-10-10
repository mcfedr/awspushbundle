<?php

namespace Mcfedr\AwsPushBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('mcfedr_aws_push')
            ->children()
                ->arrayNode("aws")
                    ->children()
                        ->scalarNode("key")->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode("secret")->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode("region")->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->variableNode("platforms")->end()
                ->booleanNode("debug")->defaultFalse()->end()
                ->scalarNode("topic_arn")->cannotBeEmpty()->end()
            ->end()
        ->end();


        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
