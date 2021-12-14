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
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Mcfedr\AwsPushBundle\McfedrAwsPushBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.(self::VERSION_ID >= 50300 ? '/config_test_53.yml' : '/config_test.yml'));
    }
}
