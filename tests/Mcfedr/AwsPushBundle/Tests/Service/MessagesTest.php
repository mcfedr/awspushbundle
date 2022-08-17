<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Tests\Service;

use Aws\Sns\SnsClient;
use Mcfedr\AwsPushBundle\Message\Message;
use Mcfedr\AwsPushBundle\Service\Messages;
use PHPUnit\Framework\TestCase;

class MessagesTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SnsClient
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(SnsClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['publish'])
            ->getMock();
    }

    public function testSend()
    {
        $messages = new Messages($this->client, []);

        $this->client
            ->expects($this->once())
            ->method('publish')
            ->with([
                'TargetArn' => 'arn',
                'Message' => '"data"',
                'MessageStructure' => 'json',
                'MessageAttributes' => [
                    'AWS.SNS.MOBILE.APNS.PUSH_TYPE' => ['DataType' => 'String', 'StringValue' => Message::PUSH_TYPE_ALERT],
                ],
            ]);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn('data');

        $message->expects($this->once())
            ->method('getCollapseKey')
            ->willReturn(Message::NO_COLLAPSE);

        $message->expects($this->once())
            ->method('getPushType')
            ->willReturn(Message::PUSH_TYPE_ALERT);

        $messages->send($message, 'arn');
    }

    public function testSendPlatforms()
    {
        $messages = new Messages($this->client, [], false, null, [Message::PLATFORM_FCM]);

        $this->client
            ->expects($this->once())
            ->method('publish')
            ->with([
                'TargetArn' => 'arn',
                'Message' => '"data"',
                'MessageStructure' => 'json',
                'MessageAttributes' => [
                    'AWS.SNS.MOBILE.APNS.PUSH_TYPE' => ['DataType' => 'String', 'StringValue' => Message::PUSH_TYPE_ALERT],
                ],
            ]);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn('data');

        $message->expects($this->once())
            ->method('isPlatformsCustomized')
            ->willReturn(false);

        $message->expects($this->once())
            ->method('getCollapseKey')
            ->willReturn(Message::NO_COLLAPSE);

        $message->expects($this->once())
            ->method('getPushType')
            ->willReturn(Message::PUSH_TYPE_ALERT);

        $message->expects($this->once())
            ->method('setPlatforms')
            ->with([Message::PLATFORM_FCM]);

        $messages->send($message, 'arn');
    }

    public function testSendPlatformsCustomized()
    {
        $messages = new Messages($this->client, [], false, null, [Message::PLATFORM_FCM]);

        $this->client
            ->expects($this->once())
            ->method('publish')
            ->with([
                'TargetArn' => 'arn',
                'Message' => '"data"',
                'MessageStructure' => 'json',
                'MessageAttributes' => [
                    'AWS.SNS.MOBILE.APNS.PUSH_TYPE' => ['DataType' => 'String', 'StringValue' => Message::PUSH_TYPE_ALERT],
                ],
            ]);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn('data');

        $message->expects($this->once())
            ->method('isPlatformsCustomized')
            ->willReturn(true);

        $message->expects($this->once())
            ->method('getCollapseKey')
            ->willReturn(Message::NO_COLLAPSE);

        $message->expects($this->once())
            ->method('getPushType')
            ->willReturn(Message::PUSH_TYPE_ALERT);

        $message->expects($this->never())
            ->method('setPlatforms');

        $messages->send($message, 'arn');
    }

    public function testSendBackground()
    {
        $messages = new Messages($this->client, []);

        $this->client
            ->expects($this->once())
            ->method('publish')
            ->with([
                'TargetArn' => 'arn',
                'Message' => '"data"',
                'MessageStructure' => 'json',
                'MessageAttributes' => [
                    'AWS.SNS.MOBILE.APNS.PUSH_TYPE' => ['DataType' => 'String', 'StringValue' => Message::PUSH_TYPE_BACKGROUND],
                ],
            ]);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn('data');

        $message->expects($this->once())
            ->method('getCollapseKey')
            ->willReturn(Message::NO_COLLAPSE);

        $message->expects($this->once())
            ->method('getPushType')
            ->willReturn(Message::PUSH_TYPE_BACKGROUND);

        $messages->send($message, 'arn');
    }

    public function testSendWithCollapseKey()
    {
        $messages = new Messages($this->client, []);

        $collapseKey = uniqid();

        $this->client
            ->expects($this->once())
            ->method('publish')
            ->with([
                'TargetArn' => 'arn',
                'Message' => '"data"',
                'MessageStructure' => 'json',
                'MessageAttributes' => [
                    'AWS.SNS.MOBILE.APNS.PUSH_TYPE' => ['DataType' => 'String', 'StringValue' => 'alert'],
                    'AWS.SNS.MOBILE.APNS.COLLAPSE_ID' => ['DataType' => 'String', 'StringValue' => $collapseKey],
                ],
            ]);

        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();

        $message->expects($this->any())
            ->method('getCollapseKey')
            ->willReturn($collapseKey);

        $message->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn('data');

        $message->expects($this->once())
            ->method('getPushType')
            ->willReturn('alert');

        $messages->send($message, 'arn');
    }

}
