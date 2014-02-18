<?php
namespace mcfedr\AWSPushBundle\Service;

use Aws\Sns\Exception\SubscriptionLimitExceededException;
use Aws\Sns\Exception\TopicLimitExceededException;
use Aws\Sns\SnsClient;
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
     * @var string
     */
    private $cacheDir;

    /**
     * @var array
     */
    private $currentTopics = [];

    /**
     * @var bool
     */
    private $debug;

    /**
     * @param SnsClient $client
     * @param LoggerInterface $logger
     * @param Messages $messages
     * @param $cacheDir
     * @param $debug
     */
    public function __construct(SnsClient $client, LoggerInterface $logger, Messages $messages, $cacheDir, $debug)
    {
        $this->sns = $client;
        $this->logger = $logger;
        $this->messages = $messages;
        $this->cacheDir = $cacheDir;
        $this->debug = $debug;
    }

    /**
     * Subscribe a device to the topic, will create new numbered topics
     * once the first is full
     *
     * @param string $deviceArn
     * @param string $topicName The base name of the topics to use
     * @throws SubscriptionLimitExceededException
     * @throws TopicLimitExceededException
     */
    public function registerDeviceOnTopic($deviceArn, $topicName)
    {
        $this->registerDevice($deviceArn, $this->getCurrentTopic($topicName));
    }

    /**
     * Send a message to all topics in the group
     *
     * @param Message $message
     * @param string $topicName
     */
    public function broadcast(Message $message, $topicName)
    {
        if($this->debug) {
            $this->logger->notice("Message would have been sent to $topicName", [
                'Message' => $message
            ]);
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
     * @param bool $retry
     * @throws SubscriptionLimitExceededException
     */
    private function registerDevice($deviceArn, Topic $topic, $retry = false)
    {
        try {
            $this->sns->subscribe(
                [
                    'TopicArn' => $topic->getArn(),
                    'Protocol' => 'application',
                    'Endpoint' => $deviceArn
                ]
            );
        } catch (SubscriptionLimitExceededException $e) {
            if ($retry) {
                throw $e;
            } else {
                $this->registerDevice(
                    $deviceArn,
                    $this->createNextTopic($topic->getName(), $topic->getNumber() + 1),
                    true
                );
            }
        }
    }

    /**
     * Find the highest numbered topic in the group
     *
     * @param string $topicName
     * @throws TopicLimitExceededException
     * @return Topic
     */
    private function getCurrentTopic($topicName)
    {
        if (isset($this->currentTopics[$topicName])) {
            return $this->currentTopics[$topicName];
        }

        $file = $this->getCacheFile($topicName);
        if (file_exists($file)) {
            $this->logger->debug(
                'Reading cached topic',
                [
                    'file' => $file
                ]
            );
            if (($topicJson = file_get_contents($file)) && $topic = json_decode($topicJson, true)) {
                $this->currentTopics[$topicName] = new Topic($topic['number'], $topic['arn'], $topic['name']);
                return $this->currentTopics[$topicName];
            } else {
                $this->logger->warning(
                    'Failed to read topic cache',
                    [
                        'topicName' => $topicName,
                        'file' => $file,
                        'topicJson' => $topicJson
                    ]
                );
            }
        }

        $current = null;

        $this->iterateTopics(
            $topicName,
            function (Topic $topic) use (&$current) {
                if (!$current || $topic->getNumber() > $current->getNumber()) {
                    $current = $topic;
                }
            }
        );

        if (!$current) {
            return $this->createNextTopic($topicName, 0);
        }

        $this->currentTopics[$topicName] = $current;
        $this->cacheTopic($current);

        return $current;
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

        $this->currentTopics[$topicName] = new Topic($number, $response['TopicArn'], $topicName);
        $this->cacheTopic($this->currentTopics[$topicName]);

        return $this->currentTopics[$topicName];
    }

    /**
     * @param string $topicName
     * @param callable $callback
     */
    private function iterateTopics($topicName, callable $callback)
    {
        $topicNameClean = preg_quote($topicName, '/');

        foreach ($this->sns->getListTopicsIterator() as $topicArn) {
            if (preg_match("/:$topicNameClean(\d*)$/", $topicArn, $matches)) {
                $callback(new Topic($matches[1] == '' ? 0 : (int)$matches[1], $topicArn, $topicName));
            }
        }
    }

    private function cacheTopic(Topic $topic)
    {
        $this->logger->debug(
            'Caching topic',
            [
                'topic' => $topic
            ]
        );

        $dir = $this->getCacheDir();
        if (!file_exists($dir)) {
            mkdir($this->getCacheDir(), 0777, true);
        }

        $file = $this->getCacheFile($topic->getName());
        if (!file_put_contents($file, json_encode($topic))) {
            $this->logger->error(
                'Failed to write topic cache',
                [
                    'topic' => $topic,
                    'file' => $file
                ]
            );
        }
    }

    private function getCacheDir()
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . 'mcfedr_aws_push';
    }

    private function getCacheFile($topicName)
    {
        return $this->getCacheDir() . DIRECTORY_SEPARATOR . $topicName;
    }
}
