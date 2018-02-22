<?php

namespace Mcfedr\AwsPushBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
{
    public function testRegisterDevice()
    {
        $client = self::createClient();

        $snsMock = $this->getMockBuilder('Aws\Sns\SnsClient')
            ->setMethods(['createPlatformEndpoint'])
            ->disableOriginalConstructor()
            ->getMock();
        $snsMock->expects($this->once())
            ->method('createPlatformEndpoint');

        $client->getContainer()->set('mcfedr_aws_push.sns_client', $snsMock);

        $client->request('POST', '/devices', [], [], [], json_encode([
            'device' => [
                'platform' => 'test',
                'deviceId' => 'abcd'
            ]
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
            'PHP_AUTH_PW' => 'password'
        ], json_encode([
            'broadcast' => [
                'message' => [
                    'text' => 'hello'
                ]
            ]
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testInvalidRegisterDevice()
    {
        $client = self::createClient();

        $client->request('POST', '/broadcast', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password'
        ], json_encode([
            'broadcastX' => [
                'message' => [
                    'text' => 'hello'
                ]
            ]
        ]));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testInvalidBroadcast()
    {
        $client = self::createClient();

        $client->request('POST', '/broadcast', [], [], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'password'
        ], json_encode([
            'broadcastX' => [
                'message' => [
                    'text' => 'hello'
                ]
            ]
        ]));
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testOtherUserBroadcast()
    {
        $client = self::createClient();

        $client->request('POST', '/broadcast', [], [], [
            'PHP_AUTH_USER' => 'other',
            'PHP_AUTH_PW' => 'password'
        ], json_encode([
            'broadcast' => [
                'message' => [
                    'text' => 'hello'
                ]
            ]
        ]));
    }
}
