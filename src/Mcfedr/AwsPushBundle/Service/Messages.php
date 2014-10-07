<?php

namespace Mcfedr\AwsPushBundle\Service;

use Aws\Sns\SnsClient;
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
     * @param SnsClient $client
     * @param array $platformARNS
     * @param LoggerInterface $logger
     * @param bool $debug
     */
    public function __construct(SnsClient $client, $platformARNS, LoggerInterface $logger, $debug)
    {
        $this->sns = $client;
        $this->arns = $platformARNS;
        $this->logger = $logger;
        $this->debug = $debug;
    }

    /**
     * Send a message to all devices on one or all platforms
     *
     * @param Message $message
     * @param string $platform
     * @throws \Mcfedr\AwsPushBundle\Exception\PlatformNotConfiguredException
     */
    public function broadcast(Message $message, $platform = null)
    {
        if ($platform != null && !isset($this->arns[$platform])) {
            throw new PlatformNotConfiguredException("There is no configured ARN for $platform");
        }

        $messageData = json_encode($message, JSON_UNESCAPED_UNICODE);

        if ($platform) {
            $this->broadcastToPlatform($messageData, $platform);
        } else {
            foreach ($this->arns as $platform => $arn) {
                $this->broadcastToPlatform($messageData, $platform);
            }
        }
    }

    /**
     * Send a message to an endpoint
     *
     * @param Message|string $message
     * @param string $endpointArn
     */
    public function send($message, $endpointArn)
    {
        if ($this->debug) {
            $this->logger->notice(
                "Message would have been sent to $endpointArn",
                [
                    'Message' => $message
                ]
            );
            return;
        }

        $this->sns->publish(
            [
                'TargetArn' => $endpointArn,
                'Message' => $message instanceof $message ? json_encode($message, JSON_UNESCAPED_UNICODE) : $message,
                'MessageStructure' => 'json'
            ]
        );
    }

    /**
     * Send a message to all devices on a platform
     *
     * @param Message|string $message
     * @param string $platform
     */
    private function broadcastToPlatform($message, $platform)
    {
        if ($this->debug) {
            $this->logger->notice(
                "Message would have been sent to $platform",
                [
                    'Message' => $message
                ]
            );
            return;
        }

        foreach ($this->sns->getListEndpointsByPlatformApplicationIterator(
            [
                'PlatformApplicationArn' => $this->arns[$platform]
            ]
        ) as $endpoint) {
            if ($endpoint['Attributes']['Enabled'] == "true") {
                try {
                    $this->send($message, $endpoint['EndpointArn']);
                } catch (\Exception $e) {
                    $this->logger->error(
                        "Failed to push to {$endpoint['EndpointArn']}",
                        [
                            'Message' => $message,
                            'Exception' => $e,
                            'Endpoint' => $endpoint
                        ]
                    );
                }
            } else {
                $this->logger->info(
                    "Disabled endpoint {$endpoint['EndpointArn']}",
                    [
                        'Message' => $message,
                        'Endpoint' => $endpoint
                    ]
                );
            }
        }
    }

}
