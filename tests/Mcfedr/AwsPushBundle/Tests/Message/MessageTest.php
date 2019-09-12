<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Tests\Message;

use Faker\Provider\Base;
use Faker\Provider\Lorem;
use Mcfedr\AwsPushBundle\Exception\MessageTooLongException;
use Mcfedr\AwsPushBundle\Message\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /**
     * @dataProvider tooLongMessage
     */
    public function testTooLong(Message $message): void
    {
        $this->expectException(MessageTooLongException::class);
        $message->setAllowTrimming(false);

        try {
            echo json_encode($message);
        } catch (\Exception $e) {
            if ($e->getPrevious() instanceof MessageTooLongException) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }

    /**
     * @dataProvider tooLongMessage
     */
    public function testTrimMessage(Message $message): void
    {
        $this->assertNotEmpty(json_encode($message));
    }

    public function tooLongMessage(): array
    {
        $adm = new Message(Lorem::text(7000));
        $adm->setPlatforms([Message::PLATFORM_ADM]);

        $apns = new Message(Lorem::text(5000));
        $apns->setPlatforms([Message::PLATFORM_APNS]);

        $apnsVoip = new Message(Lorem::text(6000));
        $apnsVoip->setPlatforms([Message::PLATFORM_APNS_VOIP]);

        $gcm = new Message(Lorem::text(6000));
        $gcm->setPlatforms([Message::PLATFORM_GCM]);

        $fcm = new Message(Lorem::text(6000));
        $fcm->setPlatforms([Message::PLATFORM_FCM]);

        return [
            [$adm],
            [$apns],
            [$apnsVoip],
            [$gcm],
            [$fcm],
        ];
    }

    /**
     * @dataProvider shortMessage
     */
    public function testShortMessage(Message $message): void
    {
        $message->setAllowTrimming(false);
        $this->assertNotEmpty(json_encode($message));
    }

    public function shortMessage(): array
    {
        $adm = new Message(Lorem::text(5050));
        $adm->setPlatforms([Message::PLATFORM_ADM]);

        $apns = new Message(Lorem::text(4000));
        $apns->setPlatforms([Message::PLATFORM_APNS]);

        $apnsVoip = new Message(Lorem::text(5000));
        $apnsVoip->setPlatforms([Message::PLATFORM_APNS_VOIP]);

        $fcm = new Message(Lorem::text(4050));
        $fcm->setPlatforms([Message::PLATFORM_FCM]);

        $gcm = new Message(Lorem::text(4050));
        $gcm->setPlatforms([Message::PLATFORM_GCM]);

        return [
            [$adm],
            [$apns],
            [$apnsVoip],
            [$gcm],
            [$fcm],
        ];
    }

    /**
     * @dataProvider text
     */
    public function testTextMessageStructure(string $text): void
    {
        $message = new Message($text);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals($text, $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);
        $this->assertArrayHasKey('expiresAfter', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(1, $admData['data']);
        $this->assertArrayHasKey('message', $admData['data']);
        $this->assertEquals($text, $admData['data']['message'], 'ADM.data.message should be the text of the message');

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('alert', $apnsData['aps']);

        $this->assertIsArray($apnsData['aps']['alert']);
        $this->assertCount(1, $apnsData['aps']['alert']);
        $this->assertArrayHasKey('body', $apnsData['aps']['alert']);
        $this->assertEquals($text, $apnsData['aps']['alert']['body'], 'APNS.aps.alert.body should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('collapse_key', $gcmData);
        $this->assertArrayHasKey('time_to_live', $gcmData);
        $this->assertArrayHasKey('delay_while_idle', $gcmData);
        $this->assertArrayHasKey('priority', $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(1, $gcmData['data']);
        $this->assertArrayHasKey('message', $gcmData['data']);
        $this->assertEquals($text, $gcmData['data']['message'], 'GCM.data.message should be the text of the message');

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('collapse_key', $fcmData);
        $this->assertArrayHasKey('time_to_live', $fcmData);
        $this->assertArrayHasKey('delay_while_idle', $fcmData);
        $this->assertArrayHasKey('priority', $fcmData);
        $this->assertArrayHasKey('notification', $fcmData);

        $this->assertIsArray($fcmData['notification']);
        $this->assertCount(1, $fcmData['notification']);
        $this->assertArrayHasKey('body', $fcmData['notification']);
        $this->assertEquals($text, $fcmData['notification']['body'], 'FCM.notification.body should be the text of the message');
    }

    public function text(): array
    {
        return [
            [Lorem::text(1000)],
        ];
    }

    /**
     * @dataProvider localized
     */
    public function testLocalizedMessageStructure(string $text, string $key, array $args): void
    {
        $message = new Message($text);
        $message->setLocalizedKey($key);
        $message->setLocalizedArguments($args);

        $string = (string) $message;
        $data = json_decode($string, true);
        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        $this->assertEquals($text, $data['default'], 'Default should be just the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertCount(2, $admData);
        $this->assertCount(3, $admData['data']);
        $this->assertEquals($text, $admData['data']['message'], 'ADM.data.message should be the text of the message');
        $this->assertEquals($key, $admData['data']['message-loc-key'], 'ADM.data.message-loc-key should be the key of the message');
        $this->assertEquals(json_encode($args), $admData['data']['message-loc-args_json'], 'ADM.data.message-loc-args should be the args of the message');

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertCount(1, $apnsData);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('alert', $apnsData['aps']);
        $this->assertIsArray($apnsData['aps']['alert']);
        $this->assertCount(2, $apnsData['aps']['alert']);
        $this->assertEquals($key, $apnsData['aps']['alert']['loc-key'], 'APNS.aps.alert.loc-key should be the key of the message');
        $this->assertEquals($args, $apnsData['aps']['alert']['loc-args'], 'APNS.aps.alert.loc-args should be the args of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertCount(3, $gcmData['data']);
        $this->assertEquals($text, $gcmData['data']['message'], 'GCM.data.message should be the text of the message');
        $this->assertEquals($key, $gcmData['data']['message-loc-key'], 'GCM.data.message-loc-key should be the key of the message');
        $this->assertEquals($args, $gcmData['data']['message-loc-args'], 'GCM.data.message-loc-args should be the args of the message');

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('notification', $fcmData);

        $this->assertIsArray($fcmData['notification']);
        $this->assertCount(3, $fcmData['notification']);
        $this->assertEquals($text, $fcmData['notification']['body'], 'FCM.notification.body should be the text of the message');
        $this->assertEquals($key, $fcmData['notification']['body_loc_key'], 'FCM.notification.body_loc_key should be the text of the message');
        $this->assertEquals($args, $fcmData['notification']['body_loc_args'], 'FCM.notification.body_loc_args should be the text of the message');
    }

    public function localized(): array
    {
        return [
            [
                Lorem::text(1000),
                Lorem::text(50),
                Lorem::words(3),
            ],
        ];
    }

    /**
     * @dataProvider localizedNoArgs
     */
    public function testLocalizedNoArgsMessageStructure(string $text, string $key): void
    {
        $message = new Message($text);
        $message->setLocalizedKey($key);

        $string = (string) $message;
        $data = json_decode($string, true);
        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        $this->assertEquals($text, $data['default'], 'Default should be just the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertCount(2, $admData);
        $this->assertCount(2, $admData['data']);
        $this->assertEquals($text, $admData['data']['message'], 'ADM.data.message should be the text of the message');
        $this->assertEquals($key, $admData['data']['message-loc-key'], 'ADM.data.message-loc-key should be the key of the message');

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertCount(1, $apnsData);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('alert', $apnsData['aps']);
        $this->assertIsArray($apnsData['aps']['alert']);
        $this->assertCount(1, $apnsData['aps']['alert']);
        $this->assertEquals($key, $apnsData['aps']['alert']['loc-key'], 'APNS.alert.key should be the key for the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertCount(5, $gcmData);
        $this->assertCount(2, $gcmData['data']);
        $this->assertEquals($text, $gcmData['data']['message'], 'GCM.data.message should be the text of the message');
        $this->assertEquals($key, $gcmData['data']['message-loc-key'], 'GCM.data.loc-key should be the key of the message');

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('notification', $fcmData);

        $this->assertIsArray($fcmData['notification']);
        $this->assertCount(2, $fcmData['notification']);
        $this->assertEquals($text, $fcmData['notification']['body'], 'FCM.notification.body should be the text of the message');
        $this->assertEquals($key, $fcmData['notification']['body_loc_key'], 'FCM.notification.body_loc_key should be the text of the message');
    }

    public function localizedNoArgs(): array
    {
        return [
            [
                Lorem::text(1000),
                Lorem::text(50),
            ],
        ];
    }

    /**
     * @dataProvider title
     */
    public function testTitleMessageStructure(string $title, string $subtitle): void
    {
        $message = new Message();
        $message->setTitle($title);
        $message->setSubtitle($subtitle);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(2, $admData['data']);
        $this->assertEquals($title, $admData['data']['title'], 'ADM.data.title should be the text of the message');
        $this->assertEquals($subtitle, $admData['data']['subtitle'], 'ADM.data.subtitle should be the text of the message');

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('alert', $apnsData['aps']);

        $this->assertIsArray($apnsData['aps']['alert']);
        $this->assertCount(2, $apnsData['aps']['alert']);
        $this->assertArrayHasKey('title', $apnsData['aps']['alert']);
        $this->assertArrayHasKey('subtitle', $apnsData['aps']['alert']);
        $this->assertEquals($title, $apnsData['aps']['alert']['title'], 'APNS.aps.alert.title should be the text of the message');
        $this->assertEquals($subtitle, $apnsData['aps']['alert']['subtitle'], 'APNS.aps.alert.subtitle should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(2, $gcmData['data']);
        $this->assertEquals($title, $gcmData['data']['title'], 'GCM.data.title should be the text of the message');
        $this->assertEquals($subtitle, $gcmData['data']['subtitle'], 'GCM.data.subtitle should be the text of the message');

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('notification', $fcmData);

        $this->assertIsArray($fcmData['notification']);
        $this->assertCount(1, $fcmData['notification']);
        $this->assertEquals($title, $fcmData['notification']['title'], 'FCM.notification.title should be the text of the message');
    }

    public function title(): array
    {
        return [
            [
                Lorem::text(1000),
                Lorem::text(1000),
            ],
        ];
    }

    /**
     * @dataProvider localizedTitle
     */
    public function testLocalizedTitleMessageStructure(string $titleKey, array $titleArgs, string $subtitleKey, array $subtitleArgs): void
    {
        $message = new Message();
        $message->setTitleLocalizedKey($titleKey);
        $message->setTitleLocalizedArguments($titleArgs);
        $message->setSubtitleLocalizedKey($subtitleKey);
        $message->setSubtitleLocalizedArguments($subtitleArgs);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(4, $admData['data']);
        $this->assertEquals($titleKey, $admData['data']['title-loc-key'], 'ADM.data.title-loc-key should be the text of the message');
        $this->assertEquals(json_encode($titleArgs), $admData['data']['title-loc-args_json'], 'ADM.data.title-loc-args should be the text of the message');
        $this->assertEquals($subtitleKey, $admData['data']['subtitle-loc-key'], 'ADM.data.subtitle-loc-key should be the text of the message');
        $this->assertEquals(json_encode($subtitleArgs), $admData['data']['subtitle-loc-args_json'], 'ADM.data.subtitle-loc-args should be the text of the message');

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('alert', $apnsData['aps']);

        $this->assertIsArray($apnsData['aps']['alert']);
        $this->assertCount(4, $admData['data']);
        $this->assertEquals($titleKey, $apnsData['aps']['alert']['title-loc-key'], 'APNS.aps.alert.title-loc-key should be the text of the message');
        $this->assertEquals($titleArgs, $apnsData['aps']['alert']['title-loc-args'], 'APNS.aps.alert.title-loc-args should be the text of the message');
        $this->assertEquals($subtitleKey, $apnsData['aps']['alert']['subtitle-loc-key'], 'APNS.aps.alert.subtitle-loc-key should be the text of the message');
        $this->assertEquals($subtitleArgs, $apnsData['aps']['alert']['subtitle-loc-args'], 'APNS.aps.alert.subtitle-loc-args should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(4, $gcmData['data']);
        $this->assertEquals($titleKey, $gcmData['data']['title-loc-key'], 'GCM.data.title-loc-key should be the text of the message');
        $this->assertEquals($titleArgs, $gcmData['data']['title-loc-args'], 'GCM.data.title-loc-args should be the text of the message');
        $this->assertEquals($subtitleKey, $gcmData['data']['subtitle-loc-key'], 'GCM.data.subtitle-loc-key should be the text of the message');
        $this->assertEquals($subtitleArgs, $gcmData['data']['subtitle-loc-args'], 'GCM.data.subtitle-loc-args should be the text of the message');

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('notification', $fcmData);
        $this->assertArrayHasKey('collapse_key', $fcmData);
        $this->assertArrayHasKey('time_to_live', $fcmData);
        $this->assertArrayHasKey('delay_while_idle', $fcmData);
        $this->assertArrayHasKey('priority', $fcmData);

        $this->assertIsArray($fcmData['notification']);
        $this->assertCount(2, $fcmData['notification']);
        $this->assertEquals($titleKey, $fcmData['notification']['title_loc_key'], 'FCM.data.title_loc_key should be the text of the message');
        $this->assertEquals($titleArgs, $fcmData['notification']['title_loc_args'], 'FCM.data.title_loc_args should be the text of the message');
    }

    public function localizedTitle(): array
    {
        return [
            [
                Lorem::text(1000),
                Lorem::words(3),
                Lorem::text(1000),
                Lorem::words(3),
            ],
        ];
    }

    /**
     * @dataProvider category
     */
    public function testCategoryMessageStructure(string $category): void
    {
        $message = new Message();
        $message->setCategory($category);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('category', $apnsData['aps']);
        $this->assertEquals($category, $apnsData['aps']['category'], 'APNS.aps.category should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('notification', $fcmData);

        $this->assertIsArray($fcmData['notification']);
        $this->assertCount(1, $fcmData['notification']);
        $this->assertEquals($category, $fcmData['notification']['android_channel_id'], 'FCM.notification.android_channel_id should be the text of the message');
    }

    public function category(): array
    {
        return [
            [
                Lorem::text(1000),
            ],
        ];
    }

    /**
     * @dataProvider badge
     */
    public function testBadgeMessageStructure(int $badge): void
    {
        $message = new Message();
        $message->setBadge($badge);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('badge', $apnsData['aps']);
        $this->assertEquals($badge, $apnsData['aps']['badge'], 'APNS.aps.badge should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(0, $fcmData['data']);
    }

    public function badge(): array
    {
        return [
            [
                random_int(0, 100),
            ],
        ];
    }

    /**
     * @dataProvider sound
     */
    public function testSoundMessageStructure(string $sound): void
    {
        $message = new Message();
        $message->setSound($sound);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(1, $admData['data']);
        $this->assertEquals($sound, $admData['data']['sound'], 'ADM.data.sound should be the text of the message');

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('sound', $apnsData['aps']);
        $this->assertEquals($sound, $apnsData['aps']['sound'], 'APNS.aps.sound should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(1, $gcmData['data']);
        $this->assertEquals($sound, $gcmData['data']['sound'], 'GCM.data.sound should be the text of the message');

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('notification', $fcmData);

        $this->assertIsArray($fcmData['notification']);
        $this->assertCount(1, $fcmData['notification']);
        $this->assertEquals($sound, $fcmData['notification']['sound'], 'FCM.notification.sound should be the text of the message');
    }

    public function sound(): array
    {
        return [
            [
                Lorem::text(1000),
            ],
        ];
    }

    public function testContentAvailableMessageStructure(): void
    {
        $message = new Message();
        $message->setContentAvailable(true);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('content-available', $apnsData['aps']);
        $this->assertEquals(1, $apnsData['aps']['content-available'], 'APNS.aps.content-available should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(0, $fcmData['data']);
    }

    /**
     * @dataProvider threadId
     */
    public function testThreadIdMessageStructure(string $threadId): void
    {
        $message = new Message();
        $message->setThreadId($threadId);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('thread-id', $apnsData['aps']);
        $this->assertEquals($threadId, $apnsData['aps']['thread-id'], 'APNS.aps.thread-id should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(0, $fcmData['data']);
    }

    public function threadId(): array
    {
        return [
            [
                Lorem::text(1000),
            ],
        ];
    }

    public function testMutableContentMessageStructure(): void
    {
        $message = new Message();
        $message->setMutableContent(true);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(1, $apnsData['aps']);
        $this->assertArrayHasKey('mutable-content', $apnsData['aps']);
        $this->assertEquals(1, $apnsData['aps']['mutable-content'], 'APNS.aps.mutable-content should be the text of the message');

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(0, $fcmData['data']);
    }

    public function testCustomMessageStructure(): void
    {
        $message = new Message();
        $message->setCustom(['some' => 'data']);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(1, $admData['data']);
        $this->assertArrayHasKey('some', $admData['data']);
        $this->assertEquals('data', $admData['data']['some']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(2, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(0, $apnsData['aps']);

        $this->assertArrayHasKey('some', $apnsData);
        $this->assertEquals('data', $apnsData['some']);

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(1, $gcmData['data']);
        $this->assertArrayHasKey('some', $gcmData['data']);
        $this->assertEquals('data', $gcmData['data']['some']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(1, $fcmData['data']);
        $this->assertArrayHasKey('some', $fcmData['data']);
        $this->assertEquals('data', $fcmData['data']['some']);
    }

    public function testAdmDataMessageStructure(): void
    {
        $message = new Message();
        $message->setAdmData(['some' => 'data']);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(1, $admData['data']);
        $this->assertArrayHasKey('some', $admData['data']);
        $this->assertEquals('data', $admData['data']['some']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(0, $apnsData['aps']);

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(0, $fcmData['data']);
    }

    public function testApnsDataMessageStructure(): void
    {
        $message = new Message();
        $message->setApnsData(['some' => 'data']);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(2, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(0, $apnsData['aps']);

        $this->assertArrayHasKey('some', $apnsData);
        $this->assertEquals('data', $apnsData['some']);

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(0, $fcmData['data']);
    }

    public function testFcmDataMessageStructure(): void
    {
        $message = new Message();
        $message->setFcmData(['some' => 'data']);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(0, $apnsData['aps']);

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(1, $fcmData['data']);
        $this->assertArrayHasKey('some', $fcmData['data']);
        $this->assertEquals('data', $fcmData['data']['some']);
    }

    public function testFcmTopLevelDataMessageStructure(): void
    {
        $message = new Message();
        $message->setFcmTopLevelData(['some' => 'data']);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(0, $apnsData['aps']);

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(0, $gcmData['data']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(6, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(0, $fcmData['data']);
        $this->assertArrayHasKey('some', $fcmData);
        $this->assertEquals('data', $fcmData['some']);
    }

    public function testGcmDataMessageStructure(): void
    {
        $message = new Message();
        $message->setGcmData(['some' => 'data']);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(2, $admData);
        $this->assertArrayHasKey('data', $admData);

        $this->assertIsArray($admData['data']);
        $this->assertCount(0, $admData['data']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(0, $apnsData['aps']);

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('data', $gcmData);

        $this->assertIsArray($gcmData['data']);
        $this->assertCount(1, $gcmData['data']);
        $this->assertArrayHasKey('some', $gcmData['data']);
        $this->assertEquals('data', $gcmData['data']['some']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('data', $fcmData);

        $this->assertIsArray($fcmData['data']);
        $this->assertCount(0, $fcmData['data']);
    }

    /**
     * @dataProvider collapseKey
     */
    public function testCollapseKeyMessageStructure(string $collapseKey): void
    {
        $message = new Message();
        $message->setCollapseKey($collapseKey);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(7, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('ADM', $data);
        $this->assertArrayHasKey('APNS', $data);
        $this->assertArrayHasKey('APNS_VOIP', $data);
        $this->assertArrayHasKey('APNS_SANDBOX', $data);
        $this->assertArrayHasKey('APNS_VOIP_SANDBOX', $data);
        $this->assertArrayHasKey('GCM', $data);

        // Default
        $this->assertEquals('', $data['default'], 'Default should be the text of the message');

        // ADM
        $admData = json_decode($data['ADM'], true);
        $this->assertIsArray($admData);
        $this->assertCount(3, $admData);
        $this->assertArrayHasKey('consolidationKey', $admData);
        $this->assertEquals($collapseKey, $admData['consolidationKey']);

        // APNS
        $this->assertEquals($data['APNS'], $data['APNS_SANDBOX'], 'The APNS and APNS_SANDBOX should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP'], 'The APNS and APNS_VOIP should match');
        $this->assertEquals($data['APNS'], $data['APNS_VOIP_SANDBOX'], 'The APNS and APNS_VOIP_SANDBOX should match');

        $apnsData = json_decode($data['APNS'], true);
        $this->assertIsArray($apnsData);
        $this->assertCount(1, $apnsData);
        $this->assertArrayHasKey('aps', $apnsData);

        $this->assertIsArray($apnsData['aps']);
        $this->assertCount(0, $apnsData['aps']);

        // GCM
        $gcmData = json_decode($data['GCM'], true);
        $this->assertIsArray($gcmData);
        $this->assertCount(5, $gcmData);
        $this->assertArrayHasKey('collapse_key', $gcmData);
        $this->assertEquals($collapseKey, $gcmData['collapse_key']);

        // FCM
        $message->setPlatforms([Message::PLATFORM_FCM]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('default', $data);
        $this->assertArrayHasKey('FCM', $data);

        $fcmData = json_decode($data['FCM'], true);
        $this->assertIsArray($fcmData);
        $this->assertCount(5, $fcmData);
        $this->assertArrayHasKey('collapse_key', $fcmData);
        $this->assertEquals($collapseKey, $fcmData['collapse_key']);
    }

    public function collapseKey(): array
    {
        return [
            [Lorem::text(1000)],
        ];
    }

    public function testAdmCustomData(): void
    {
        $message = new Message();
        $message->setCustom([
            'simple' => 'Hello',
            'complicated' => [
                'inner' => 'values',
            ],
        ]);

        $string = (string) $message;
        $data = json_decode($string, true);

        $admData = json_decode($data['ADM'], true);

        $this->assertCount(2, $admData['data']);
        $this->assertArrayHasKey('simple', $admData['data']);
        $this->assertEquals('Hello', $admData['data']['simple']);
        $this->assertArrayHasKey('complicated_json', $admData['data']);
        $this->assertEquals(json_encode([
            'inner' => 'values',
        ]), $admData['data']['complicated_json']);
    }

    /**
     * @dataProvider ttl
     */
    public function testTtl(int $ttl): void
    {
        $message = new Message(Lorem::text(1000));
        $message->setTtl($ttl);

        $string = (string) $message;
        $data = json_decode($string, true);

        $gcmData = json_decode($data['GCM'], true);
        $this->assertEquals($ttl, $gcmData['time_to_live']);

        $admData = json_decode($data['ADM'], true);
        $this->assertEquals($ttl, $admData['expiresAfter']);
    }

    public function ttl(): array
    {
        return [
            [Base::numberBetween(60, 2678400)],
        ];
    }

    public function testPlatforms(): void
    {
        $message = new Message();

        $this->assertEquals([Message::PLATFORM_ADM, Message::PLATFORM_APNS, Message::PLATFORM_APNS_VOIP, Message::PLATFORM_GCM], $message->getPlatforms());
        $this->assertFalse($message->isPlatformsCustomized());

        $message->setPlatforms([Message::PLATFORM_FCM]);
        $this->assertEquals([Message::PLATFORM_FCM], $message->getPlatforms());
        $this->assertTrue($message->isPlatformsCustomized());
    }
}
