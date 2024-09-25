<?php

declare(strict_types=1);

use Symfony\Component\Config\Loader\LoaderInterface;

class TestKernel extends Symfony\Component\HttpKernel\Kernel
{
    public function registerBundles(): array
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Mcfedr\AwsPushBundle\McfedrAwsPushBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $configFile = 'config_test_64.yml';
        if (self::VERSION_ID < 60200) {
            $configFile = 'config_test.yml';
        } elseif (self::VERSION_ID >= 60200 && self::VERSION_ID < 60400) {
            $configFile = 'config_test_62.yml';
        }
        $loader->load(__DIR__.'/'.$configFile);
    }
}
