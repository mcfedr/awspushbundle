<?php

namespace mcfedr\AWSPushBundle\Service;

use Aws\Sns\SnsClient;
use mcfedr\AWSPushBundle\Exception\PlatformNotConfiguredException;
use mcfedr\AWSPushBundle\Message\Message;

class Messages {

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

    public function __construct(SnsClient $client, $platformARNS, $logger) {
        $this->sns = $client;
        $this->arns = $platformARNS;
        $this->logger = $logger;
    }

    /**
     * Send a message to all devices on one or all platforms
     *
     * @param Message $message
     * @param string $platform
     * @throws \mcfedr\AWSPushBundle\Exception\PlatformNotConfiguredException
     */
    public function broadcast(Message $message, $platform = null) {
        if($platform != null && !isset($this->arns[$platform])) {
            throw new PlatformNotConfiguredException("There is no configured ARN for $platform");
        }

        $messageData = json_encode($message, JSON_UNESCAPED_UNICODE);

        if($platform) {
            $this->broadcastToPlatform($messageData, $platform);
        }
        else {
            foreach($this->arns as $platform => $arn) {
                $this->broadcastToPlatform($messageData, $platform);
            }
        }
    }

    /**
     * Send a message to one device
     *
     * @param Message|string $message
     * @param string $deviceEndpoint
     */
    public function send($message, $deviceEndpoint) {
        $this->sns->publish([
            'TargetArn' => $deviceEndpoint,
            'Message' => $message instanceof $message ? json_encode($message, JSON_UNESCAPED_UNICODE) : $message,
            'MessageStructure' => 'json'
        ]);
    }

    /**
     * Send a message to all devices on a platform
     *
     * @param Message|string $message
     * @param string $platform
     */
    private function broadcastToPlatform($message, $platform) {
        foreach($this->sns->getListEndpointsByPlatformApplicationIterator([
            'PlatformApplicationArn' => $this->arns[$platform]
        ]) as $endpoint) {
            try {
                $this->send($message, $endpoint['EndpointArn']);
            }
            catch(\Exception $e) {
                $this->logger->error("Failed to push to {$endpoint['EndpointArn']}", [
                    'Message' => $message,
                    'Exception' => $e
                ]);
            }
        }
    }

}
