<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Tests\DependencyInjection;

use Aws\Sns\SnsClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class McfedrAwsPushExtensionTest extends WebTestCase
{
    public function testSnsClientDeclared(): void
    {
        $client = self::createClient();

        $sns = $client->getContainer()->get('mcfedr_aws_push.sns_client');

        $this->assertInstanceOf(SnsClient::class, $sns);
    }
}
