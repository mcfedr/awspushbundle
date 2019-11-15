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
    /**
     * @var SnsClient
     */
    private $sns;

    /**
     * @var array
     */
    private $arns;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $debug;

    /**
     * Overrides the default platforms settings in Messages.
     *
     * @var ?array
     *
     * @see Message::$platforms
     */
    private $platforms;

    public function __construct(SnsClient $client, array $platformARNS, bool $debug = false, LoggerInterface $logger = null, array $platforms = null)
    {
        $this->sns = $client;
        $this->arns = $platformARNS;
        $this->logger = $logger;
        $this->debug = $debug;
        if (null !== $platforms) {
            $this->platforms = $platforms;
        }
    }

    /**
     * Send a message to all devices on one or all platforms.
     *
     * @param string $platform
     *
     * @throws PlatformNotConfiguredException
     * @throws MessageTooLongException
     */
    public function broadcast(Message $message, ?string $platform = null)
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
    public function send($message, string $endpointArn)
    {
        if ($this->debug) {
            $this->logger && $this->logger->notice(
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

        $this->sns->publish(
            [
                'TargetArn' => $endpointArn,
                'Message' => $this->encodeMessage($message),
                'MessageStructure' => 'json',
                'MessageAttributes' => [
                    'AWS.SNS.MOBILE.APNS.PUSH_TYPE' => ['DataType' => 'String', 'StringValue' => $message->getPushType()],
                ],
            ]
        );
    }

    /**
     * @throws MessageTooLongException
     */
    private function encodeMessage(Message $message): string
    {
        try {
            $json = json_encode($message, JSON_UNESCAPED_UNICODE);

            return $json;
        } catch (\Exception $e) {
            if ($e->getPrevious() instanceof MessageTooLongException) {
                throw $e->getPrevious();
            }
            throw $e;
        }
    }

    /**
     * Send a message to all devices on a platform.
     *
     * @param Message|string $message
     */
    private function broadcastToPlatform($message, string $platform)
    {
        if ($this->debug) {
            $this->logger && $this->logger->notice(
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
                        $this->logger && $this->logger->error(
                            "Failed to push to {$endpoint['EndpointArn']}",
                            [
                                'Message' => $message,
                                'Exception' => $e,
                                'Endpoint' => $endpoint,
                            ]
                        );
                    }
                } else {
                    $this->logger && $this->logger->info(
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
