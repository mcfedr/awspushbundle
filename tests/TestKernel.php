<?php


class TestKernel extends Symfony\Component\HttpKernel\Kernel
{
    public function registerBundles()
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Mcfedr\AwsPushBundle\McfedrAwsPushBundle()
        ];
    }

    public function registerContainerConfiguration(\Symfony\Component\Config\Loader\LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config_test.yml');
    }
}
