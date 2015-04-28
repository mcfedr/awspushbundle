<?php


namespace Mcfedr\AwsPushBundle\Tests\Message;


use Faker\Provider\Lorem;
use Mcfedr\AwsPushBundle\Exception\MessageTooLongException;
use Mcfedr\AwsPushBundle\Message\Message;

class MessageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider tooLongMessage
     * @expectedException \Mcfedr\AwsPushBundle\Exception\MessageTooLongException
     */
    public function testTooLong(Message $message)
    {
        try {
            echo json_encode($message);
        } catch (\Exception $e) {
            if ($e->getPrevious() instanceof MessageTooLongException) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }

    public function tooLongMessage()
    {
        $ios = new Message();
        $ios->setCustom([
            'data' => Lorem::text(3000)
        ]);

        $android = new Message();
        $android->setCustom([
            'data' => Lorem::text(6000)
        ]);
        $android->setPlatforms([Message::PLATFORM_GCM]);

        return [
            [
                $ios
            ],
            [
                $android
            ]
        ];
    }

    /**
     * @dataProvider shortMessage
     */
    public function testShortMessage(Message $message)
    {
        $this->assertNotEmpty(json_encode($message));
    }

    public function shortMessage()
    {
        $ios = new Message();
        $ios->setCustom([
            'data' => Lorem::text(2000)
        ]);

        $android = new Message();
        $android->setCustom([
            'data' => Lorem::text(4050)
        ]);
        $android->setPlatforms([Message::PLATFORM_GCM]);

        return [
            [
                $ios
            ],
            [
                $android
            ]
        ];
    }

    /**
     * @dataProvider trimMessage
     */
    public function testTrimMessage(Message $message)
    {
        $this->assertNotEmpty(json_encode($message));
    }

    public function trimMessage()
    {
        return [
            [
                new Message(Lorem::text(10000))
            ]
        ];
    }
}
