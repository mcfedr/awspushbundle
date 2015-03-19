<?php
namespace Mcfedr\AwsPushBundle\Service;

use Aws\Sns\SnsClient;
use Mcfedr\AwsPushBundle\Message\Message;
use Psr\Log\LoggerInterface;

/**
 * @deprecated Use the SnsClient directly to deal with topics
 * @see SnsClient
 */
class Topics
{

    /**
     * @var SnsClient
     */
    private $sns;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Messages
     */
    private $messages;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param SnsClient $client
     * @param LoggerInterface $logger
     * @param Messages $messages
     * @param $debug
     */
    public function __construct(SnsClient $client, Messages $messages, $debug, LoggerInterface $logger = null)
    {
        $this->sns = $client;
        $this->logger = $logger;
        $this->messages = $messages;
        $this->debug = $debug;
    }

    /**
     * Subscribe a device to the topic, will create new numbered topics
     * once the first is full
     *
     * @param string $deviceArn
     * @param string $topicArn The base name of the topics to use
     * @deprecated use SnsClient directly to subscribe
     * @see SnsClient::subscribe
     */
    public function registerDeviceOnTopic($deviceArn, $topicArn)
    {
        $this->sns->subscribe(
            [
                'TopicArn' => $topicArn,
                'Protocol' => 'application',
                'Endpoint' => $deviceArn
            ]
        );
    }

    /**
     * Send a message to all topics in the group
     *
     * @param Message $message
     * @param string $topicArn
     * @deprecated Use Messages send method and pass the topicArn as the destination
     * @see Messages::send
     */
    public function broadcast(Message $message, $topicArn)
    {
        if ($this->debug) {
            $this->logger && $this->logger->notice(
                "Message would have been sent to $topicArn",
                [
                    'Message' => $message
                ]
            );
            return;
        }

        $this->messages->send($message, $topicArn);
    }
}
