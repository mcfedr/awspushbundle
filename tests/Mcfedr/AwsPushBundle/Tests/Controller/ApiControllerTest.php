<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ApiControllerTest extends WebTestCase
{
    public function testRegisterDevice()
    {
        $client = self::createClient();

        $snsMock = $this->getMockBuilder('Aws\Sns\SnsClient')
            ->addMethods(['createPlatformEndpoint'])
            ->disableOriginalConstructor()
            ->getMock();
        $snsMock->expects($this->once())
            ->method('createPlatformEndpoint')
            ->willReturn(['EndpointArn' => 'some:arn']);

        $client->getContainer()->set('mcfedr_aws_push.sns_client', $snsMock);

        $client->request('POST', '/devices', [], [], [], json_encode([
            'device' => [
                'platform' => 'test',
                'deviceId' => 'abcd',
            ],
        ]));
    }

    public function testBroadcast()
    {
        $client = self::createClient();
        $snsMock = $this->getMockBuilder('Aws\Sns\SnsClient')
            ->setMethods(['publish'])
            ->disableOriginalConstructor()
            ->getMock();
        $snsMock->expects($this->once())
            ->method('publish');

        $client->getContainer()->set('mcfedr_aws_push.sns_client', $snsMock);

        $client->request('POST', '/broadcast', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
        ], json_encode([
            'broadcast' => [
                'message' => [
                    'text' => 'hello',
                ],
            ],
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testInvalidRegisterDevice()
    {
        $this->expectException(BadRequestHttpException::class);

        $client = self::createClient();
        $client->catchExceptions(false);

        $client->request('POST', '/devices', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
        ], json_encode([
            'device' => [
                'deviceId' => 'abcd',
            ],
        ]));
    }

    public function testInvalidBroadcast()
    {
        $this->expectException(BadRequestHttpException::class);

        $client = self::createClient();
        $client->catchExceptions(false);

        $client->request('POST', '/broadcast', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password',
        ], json_encode([
            'broadcastX' => [
                'message' => [
                    'text' => 'hello',
                ],
            ],
        ]));
    }

    public function testOtherUserBroadcast()
    {
        $this->expectException(AccessDeniedException::class);

        $client = self::createClient();
        $client->catchExceptions(false);

        $client->request('POST', '/broadcast', [], [], [
            'PHP_AUTH_USER' => 'other',
            'PHP_AUTH_PW' => 'password',
        ], json_encode([
            'broadcast' => [
                'message' => [
                    'text' => 'hello',
                ],
            ],
        ]));
    }
}
