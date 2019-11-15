<?php

declare(strict_types=1);

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
     * @param LoggerInterface $logger
     * @param $debug
     */
    public function __construct(SnsClient $client, Messages $messages, bool $debug, LoggerInterface $logger = null)
    {
        $this->sns = $client;
        $this->logger = $logger;
        $this->messages = $messages;
        $this->debug = $debug;
    }

    /**
     * Create a topic.
     *
     * @param string $name Topic name
     *
     * @return string The topic ARN
     *
     * @deprecated use SnsClient directly to subscribe
     */
    public function createTopic(string $name): string
    {
        $res = $this->sns->createTopic(
            [
                'Name' => $name,
            ]
        );

        return $res['TopicArn'];
    }

    /**
     * Delete a topic.
     *
     * @param string $topicArn Topic ARN
     *
     * @deprecated use SnsClient directly to subscribe
     */
    public function deleteTopic(string $topicArn)
    {
        $this->sns->deleteTopic(
            [
                'TopicArn' => $topicArn,
            ]
        );
    }

    /**
     * Subscribe a device to the topic, will create new numbered topics
     * once the first is full.
     *
     * @param string $topicArn The base name of the topics to use
     *
     * @deprecated use SnsClient directly to subscribe
     * @see SnsClient::subscribe
     */
    public function registerDeviceOnTopic(string $deviceArn, string $topicArn)
    {
        $this->sns->subscribe(
            [
                'TopicArn' => $topicArn,
                'Protocol' => 'application',
                'Endpoint' => $deviceArn,
            ]
        );
    }

    /**
     * Send a message to all topics in the group.
     *
     * @deprecated Use Messages send method and pass the topicArn as the destination
     * @see Messages::send
     */
    public function broadcast(Message $message, string $topicArn)
    {
        if ($this->debug) {
            $this->logger && $this->logger->notice(
                "Message would have been sent to $topicArn",
                [
                    'Message' => $message,
                ]
            );

            return;
        }

        $this->messages->send($message, $topicArn);
    }
}
