<?php

namespace Mcfedr\AwsPushBundle\Tests\DependencyInjection\McfedrAwsPushExtension;

use Aws\Sns\SnsClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class McfedrAwsPushExtensionTest extends WebTestCase
{
    public function testRegisterDevice()
    {
        $client = self::createClient();

        $sns = $client->getContainer()->get('mcfedr_aws_push.sns_client');

        $this->assertInstanceOf(SnsClient::class, $sns);
    }
}
