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

    /**
     * @dataProvider text
     */
    public function testTextMessageStructure($text)
    {
        $message = new Message($text);
        $string = (string) $message;

        $data = json_decode($string, true);

        $this->assertInternalType('array', $data);
        $this->assertCount(5, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);
        $this->assertArrayHasKey('ADM', $data);

        $this->assertEquals($text, $data['default'], 'Default should be just the text of the message');
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertInternalType('array', $apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertInternalType('array', $apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('alert', $apnsData['aps']);
        $this->assertEquals($text, $apnsData['aps']['alert'], 'APNS.aps.alert should be the text of the message');

        $gcmData = json_decode($data['GCM'], true);
        $this->assertInternalType('array', $gcmData);
        $this->assertCount(4, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);
        $this->assertArrayHasKey('collapse_key', $gcmData);
        $this->assertArrayHasKey('time_to_live', $gcmData);
        $this->assertArrayHasKey('delay_while_idle', $gcmData);

        $this->assertInternalType('array', $gcmData['data']);
        $this->assertCount(1, $gcmData['data']);
        $this->assertEquals($text, $gcmData['data']['message'], 'GCM.data.message should be the text of the message');

        $admData = json_decode($data['ADM'], true);
        $this->assertInternalType('array', $admData);
        $this->assertCount(1, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertInternalType('array', $admData['data']);
        $this->assertCount(1, $admData['data']);
        $this->assertEquals($text, $admData['data']['message'], 'ADM.data.message should be the text of the message');
    }

    public function text()
    {
        return [
            [Lorem::text(1000)]
        ];
    }
}
