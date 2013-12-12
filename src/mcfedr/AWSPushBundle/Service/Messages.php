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

        if($platform) {
            $this->broadcastToPlatform($message, $platform);
        }
        else {
            foreach($this->arns as $platform => $arn) {
                $this->broadcastToPlatform($message, $platform);
            }
        }
    }

    /**
     * Send a message to one device
     *
     * @param Message $message
     * @param string $deviceEndpoint
     */
    public function send(Message $message, $deviceEndpoint) {
        $this->sns->publish([
            'TargetArn' => $deviceEndpoint,
            'Message' => json_encode($message),
            'MessageStructure' => 'json'
        ]);
    }

    private function broadcastToPlatform(Message $message, $platform) {
        foreach($this->sns->getListEndpointsByPlatformApplicationIterator([
            'PlatformApplicationArn' => $this->arns[$platform]
        ]) as $endpoint) {
            try {
                $this->send($message, $endpoint['EndpointArn']);
            }
            catch(\Exception $e) {
                $this->logger->error("Failed to push to {$endpoint['EndpointArn']}", [
                    'Message' => $message
                ]);
            }
        }
    }

}
