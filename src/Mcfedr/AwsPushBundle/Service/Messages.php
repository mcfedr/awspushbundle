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
     * @param SnsClient       $client
     * @param array           $platformARNS
     * @param LoggerInterface $logger
     * @param bool            $debug
     */
    public function __construct(SnsClient $client, array $platformARNS, bool $debug, LoggerInterface $logger = null)
    {
        $this->sns = $client;
        $this->arns = $platformARNS;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * Send a message to all devices on one or all platforms.
     *
     * @param Message $message
     * @param string  $platform
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
     * @param string         $endpointArn
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

        $this->sns->publish(
            [
                'TargetArn' => $endpointArn,
                'Message' => $this->encodeMessage($message),
                'MessageStructure' => 'json',
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
     * @param string         $platform
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
