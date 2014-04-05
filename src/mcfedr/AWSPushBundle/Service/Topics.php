<?php
namespace mcfedr\AWSPushBundle\Service;

use Aws\Sns\Exception\SubscriptionLimitExceededException;
use Aws\Sns\Exception\TopicLimitExceededException;
use Aws\Sns\SnsClient;
use Doctrine\Common\Cache\Cache;
use mcfedr\AWSPushBundle\Message\Message;
use mcfedr\AWSPushBundle\Topic\Topic;
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
     * @var Cache
     */
    protected $cache;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param SnsClient $client
     * @param LoggerInterface $logger
     * @param Messages $messages
     * @param \Doctrine\Common\Cache\Cache $cache
     * @param $debug
     * @internal param $cacheDir
     */
    public function __construct(SnsClient $client, LoggerInterface $logger, Messages $messages, Cache $cache, $debug)
    {
        $this->sns = $client;
        $this->logger = $logger;
        $this->messages = $messages;
        $this->cache = $cache;
        $this->debug = $debug;
    }

    /**
     * Subscribe a device to the topic, will create new numbered topics
     * once the first is full
     *
     * @param string $deviceArn
     * @param string $topicName The base name of the topics to use
     * @throws TopicLimitExceededException
     */
    public function registerDeviceOnTopic($deviceArn, $topicName)
    {
        $lastTopic = null;
        if (!$this->iterateTopics(
            $topicName,
            function (Topic $topic) use ($deviceArn, &$lastTopic) {
                $lastTopic = $topic;
                try {
                    $this->registerDevice($deviceArn, $topic);
                    return true;
                } catch (SubscriptionLimitExceededException $e) {
                    return false;
                }
            }
        )
        ) {
            $this->registerDevice(
                $deviceArn,
                $this->createNextTopic($lastTopic->getName(), $lastTopic->getNumber() + 1)
            );
        }
    }

    /**
     * Send a message to all topics in the group
     *
     * @param Message $message
     * @param string $topicName
     */
    public function broadcast(Message $message, $topicName)
    {
        if ($this->debug) {
            $this->logger->notice(
                "Message would have been sent to $topicName",
                [
                    'Message' => $message
                ]
            );
            return;
        }

        $messages = $this->messages;
        $messageData = json_encode($message, JSON_UNESCAPED_UNICODE);

        $this->iterateTopics(
            $topicName,
            function (Topic $topic) use ($messageData, $messages, $topicName) {
                try {
                    $messages->send($messageData, $topic->getArn());
                } catch (\Exception $e) {
                    $this->logger->error(
                        "Failed to push to $topicName",
                        [
                            'messageData' => $messageData,
                            'exception' => $e,
                            'topic' => $topic
                        ]
                    );
                }
            }
        );
    }

    /**
     * Internal function to subscribe device
     * Retries with a new topic if it failed once
     *
     * @internal
     * @param string $deviceArn
     * @param Topic $topic
     * @throws SubscriptionLimitExceededException
     */
    private function registerDevice($deviceArn, Topic $topic)
    {
        $this->sns->subscribe(
            [
                'TopicArn' => $topic->getArn(),
                'Protocol' => 'application',
                'Endpoint' => $deviceArn
            ]
        );
    }

    /**
     * Create a new topic in the group
     *
     * @param string $topicName
     * @param int $number
     * @throws TopicLimitExceededException
     * @return Topic
     */
    private function createNextTopic($topicName, $number)
    {
        $response = $this->sns->createTopic(
            [
                'Name' => $topicName . ($number > 0 ? $number : '')
            ]
        );

        $topic = new Topic($number, $response['TopicArn'], $topicName);

        if ($this->cache) {
            $cacheKey = $this->getCacheKey($topicName);
            $topics = $this->cache->fetch($cacheKey);
            if (!$topics) {
                $topics = [];
            }
            $topics[] = $topic;
            $this->cache->save($cacheKey, $topics);
        }

        return $topic;
    }

    /**
     * @param string $topicName
     * @param callable $callback can return true to break from the loop
     * @return bool true if a call to callback returned true
     */
    public function iterateTopics($topicName, callable $callback)
    {
        if ($this->cache) {
            $topics = $this->cache->fetch($this->getCacheKey($topicName));
            if ($topics) {
                foreach ($topics as $topic) {
                    if ($callback($topic)) {
                        return true;
                    }
                }

                return false;
            }
        }

        $topics = [];
        $topicNameQuoted = preg_quote($topicName, '/');
        $ret = false;

        foreach ($this->sns->getListTopicsIterator() as $topicArn) {
            if (preg_match("/:$topicNameQuoted(\d*)$/", $topicArn, $matches)) {
                $topic = new Topic($matches[1] == '' ? 0 : (int)$matches[1], $topicArn, $topicName);
                $topics[] = $topic;
                if (!$ret && $callback($topic)) {
                    $ret = true;
                }
            }
        }

        if ($this->cache) {
            $this->cache->save($this->getCacheKey($topicName), $topics);
        }

        return $ret;
    }

    private function getCacheKey($topicName) {
        return "'mcfedr_aws_push.$topicName";
    }
}
