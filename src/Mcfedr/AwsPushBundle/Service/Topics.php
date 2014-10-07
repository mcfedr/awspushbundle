<?php
namespace Mcfedr\AwsPushBundle\Service;

use Aws\Sns\Exception\SubscriptionLimitExceededException;
use Aws\Sns\SnsClient;
use Mcfedr\AwsPushBundle\Message\Message;
use Psr\Log\LoggerInterface;

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
    public function __construct(SnsClient $client, LoggerInterface $logger, Messages $messages, $debug)
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
     * @throws SubscriptionLimitExceededException
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
     */
    public function broadcast(Message $message, $topicArn)
    {
        if ($this->debug) {
            $this->logger->notice(
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
