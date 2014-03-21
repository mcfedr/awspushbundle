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
    private $topics = [];

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
        $this->topics[$topicName][] = $topic;
        $this->cacheTopic($topicName);

        return $topic;
    }

    /**
     * @param string $topicName
     * @param callable $callback can return true to break from the loop
     * @return bool true if a call to callback returned true
     */
    private function iterateTopics($topicName, callable $callback)
    {
        if (!isset($this->topics[$topicName])
            && ($cacheFile = $this->getCacheFile($topicName))
            && file_exists($cacheFile)
        ) {
            $this->logger->debug(
                'Reading cached topic',
                [
                    'file' => $cacheFile
                ]
            );
            if (($topicJson = file_get_contents($cacheFile))
                && ($topics = json_decode($topicJson, true))
            ) {
                $this->topics[$topicName] = array_map(
                    function ($topic) {
                        return new Topic($topic['number'], $topic['arn'], $topic['name']);
                    },
                    $topics
                );
            } else {
                $this->logger->warning(
                    'Failed to read topic cache',
                    [
                        'topicName' => $topicName,
                        'file' => $cacheFile,
                        'topicJson' => $topicJson
                    ]
                );
            }
        }

        if (isset($this->topics[$topicName])) {
            foreach ($this->topics[$topicName] as $topic) {
                if ($callback($topic)) {
                    return true;
                }
            }

            return false;
        }

        $this->topics[$topicName] = [];
        $topicNameClean = preg_quote($topicName, '/');
        $ret = false;

        foreach ($this->sns->getListTopicsIterator() as $topicArn) {
            if (preg_match("/:$topicNameClean(\d*)$/", $topicArn, $matches)) {
                $topic = new Topic($matches[1] == '' ? 0 : (int)$matches[1], $topicArn, $topicName);
                $this->topics[$topicName][] = $topic;
                if (!$ret && $callback($topic)) {
                    $ret = true;
                }
            }
        }

        $this->cacheTopic($topicName);

        return $ret;
    }

    private function cacheTopic($topicName)
    {
        $this->logger->debug(
            'Caching topic',
            [
                'topicName' => $topicName
            ]
        );

        $dir = $this->getCacheDir();
        if (!file_exists($dir)) {
            mkdir($this->getCacheDir(), 0777, true);
        }

        $file = $this->getCacheFile($topicName);
        if (!file_put_contents($file, json_encode($this->topics[$topicName]))) {
            $this->logger->error(
                'Failed to write topic cache',
                [
                    'topicName' => $topicName,
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
