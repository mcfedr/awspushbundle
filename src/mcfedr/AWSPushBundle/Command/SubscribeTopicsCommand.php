<?php
namespace mcfedr\AWSPushBundle\Command;

use Aws\Sns\Exception\SubscriptionLimitExceededException;
use Aws\Sns\Exception\TopicLimitExceededException;
use Aws\Sns\SnsClient;
use mcfedr\AWSPushBundle\Service\Topics;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SubscribeTopicsCommand extends Command
{
    /**
     * @var Topics
     */
    private $topics;

    /**
     * @var string
     */
    private $topicName;

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
     * @param Topics $topics
     * @param string $topicName
     * @param SnsClient $sns
     * @param array $arns
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(Topics $topics, $topicName, SnsClient $sns, $arns, LoggerInterface $logger)
    {
        parent::__construct();

        $this->topics = $topics;
        $this->topicName = $topicName;
        $this->sns = $sns;
        $this->arns = $arns;
        $this->logger = $logger;
    }


    protected function configure()
    {
        $this
            ->setName('mcfedr:aws:subscribe')
            ->setDescription('Subscribe existing devices to the topic');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            foreach ($this->arns as $platform => $arn) {
                $this->subscribePlatform($platform);
            }
        } catch (SubscriptionLimitExceededException $e) {
            $this->logger->error(
                'Failed to subscription to topic',
                [
                    'exception' => $e
                ]
            );
        } catch (TopicLimitExceededException $e) {
            $this->logger->error(
                'Failed to create topic',
                [
                    'exception' => $e
                ]
            );
        }
    }

    private function subscribePlatform($platform)
    {
        foreach ($this->sns->getListEndpointsByPlatformApplicationIterator(
                     [
                         'PlatformApplicationArn' => $this->arns[$platform]
                     ]
                 ) as $endpoint) {
            $this->logger->info(
                'Subscribing device to topic',
                [
                    'device' => $endpoint['EndpointArn'],
                    'topic' => $this->topicName,
                    'platform' => $platform
                ]
            );
            $this->topics->registerDeviceOnTopic($endpoint['EndpointArn'], $this->topicName);
        }
    }
}
