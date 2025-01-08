<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Service;

use Aws\Sns\SnsClient;
use Mcfedr\AwsPushBundle\Exception\MessageTooLongException;
use Mcfedr\AwsPushBundle\Exception\PlatformNotConfiguredException;
use Mcfedr\AwsPushBundle\Message\Message;
use Psr\Log\LoggerInterface;

class Messages
{
    private SnsClient $sns;

    private array $arns;

    private ?LoggerInterface $logger;

    private bool $debug;

    /**
     * Overrides the default platforms settings in Messages.
     *
     * @see Message::$platforms
     */
    private ?array $platforms;

    public function __construct(SnsClient $client, array $platformARNS, bool $debug = false, ?LoggerInterface $logger = null, ?array $platforms = null)
    {
        $this->sns = $client;
        $this->arns = $platformARNS;
        $this->logger = $logger;
        $this->debug = $debug;
        $this->platforms = $platforms;
    }

    /**
     * Send a message to all devices on one or all platforms.
     *
     * @throws PlatformNotConfiguredException
     * @throws MessageTooLongException
     */
    public function broadcast(Message $message, ?string $platform = null): void
    {
        if (null !== $platform && !isset($this->arns[$platform])) {
            throw new PlatformNotConfiguredException("There is no configured ARN for $platform");
        }

        if ($platform) {
            $this->broadcastToPlatform($message, $platform);
        } else {
            foreach ($this->arns as $platform => $arn) {
                $this->broadcastToPlatform($message, $platform);
            }
        }
    }

    /**
     * Send a message to an endpoint.
     *
     * @param Message|string $message
     *
     * @throws MessageTooLongException
     */
    public function send($message, string $endpointArn): void
    {
        if ($this->debug) {
            $this->logger?->notice(
                "Message would have been sent to $endpointArn",
                [
                    'Message' => $message,
                ]
            );

            return;
        }

        if (!($message instanceof Message)) {
            $message = new Message($message);
        }

        if ($this->platforms !== null && !$message->isPlatformsCustomized()) {
            $message->setPlatforms($this->platforms);
        }

        $messageAttributes = [
            'AWS.SNS.MOBILE.APNS.PUSH_TYPE' => ['DataType' => 'String', 'StringValue' => $message->getPushType()],
        ];

        if ($topic = $message->getApnsTopic()) {
            $messageAttributes['AWS.SNS.MOBILE.APNS.TOPIC'] = ['DataType' => 'String', 'StringValue' => $topic];
        }

        if ($message->getCollapseKey() != Message::NO_COLLAPSE) {
            $messageAttributes['AWS.SNS.MOBILE.APNS.COLLAPSE_ID'] = ['DataType' => 'String', 'StringValue' => $message->getCollapseKey()];
        }

        $this->sns->publish(
            [
                'TargetArn' => $endpointArn,
                'Message' => json_encode($message, JSON_UNESCAPED_UNICODE),
                'MessageStructure' => 'json',
                'MessageAttributes' => $messageAttributes,
            ]
        );
    }

    /**
     * Send a message to all devices on a platform.
     *
     * @param Message|string $message
     */
    private function broadcastToPlatform($message, string $platform): void
    {
        if ($this->debug) {
            $this->logger?->notice(
                "Message would have been sent to $platform",
                [
                    'Message' => $message,
                ]
            );

            return;
        }

        foreach ($this->sns->getPaginator('ListEndpointsByPlatformApplication', [
            'PlatformApplicationArn' => $this->arns[$platform],
        ]) as $endpointsResult) {
            foreach ($endpointsResult['Endpoints'] as $endpoint) {
                if ($endpoint['Attributes']['Enabled'] == 'true') {
                    try {
                        $this->send($message, $endpoint['EndpointArn']);
                    } catch (\Exception $e) {
                        $this->logger?->error(
                            "Failed to push to {$endpoint['EndpointArn']}",
                            [
                                'Message' => $message,
                                'Exception' => $e,
                                'Endpoint' => $endpoint,
                            ]
                        );
                    }
                } else {
                    $this->logger?->info(
                        "Disabled endpoint {$endpoint['EndpointArn']}",
                        [
                            'Message' => $message,
                            'Endpoint' => $endpoint,
                        ]
                    );
                }
            }
        }
    }
}
