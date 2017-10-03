<?php

namespace Mcfedr\AwsPushBundle\Command;

use Aws\Sns\Exception\SnsException;
use Aws\Sns\SnsClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SubscribeTopicsCommand extends Command
{
    /**
     * @var string
     */
    private $topicArn;

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
     * @param string                   $topicArn
     * @param SnsClient                $sns
     * @param array                    $arns
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct($topicArn, SnsClient $sns, $arns, LoggerInterface $logger = null)
    {
        $this->topicArn = $topicArn;
        $this->sns = $sns;
        $this->arns = $arns;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('mcfedr:aws:subscribe')
            ->setDescription('Subscribe existing devices to the topic')
            ->addOption(
                'topic',
                null,
                InputOption::VALUE_REQUIRED,
                'The topic to subscribe devices to',
                $this->topicArn
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            foreach ($this->arns as $platform => $arn) {
                $this->subscribePlatform($platform, $input->getOption('topic'));
            }
        } catch (SnsException $e) {
            $this->logger && $this->logger->error('Failed to create topic', [
                'exception' => $e
            ]);
        }
    }

    private function subscribePlatform($platform, $topic)
    {
        foreach ($this->sns->getPaginator('ListEndpointsByPlatformApplication', [
            'PlatformApplicationArn' => $this->arns[$platform]
        ]) as $endpointsResult) {
            foreach ($endpointsResult['Endpoints'] as $endpoint) {
                $this->logger && $this->logger->info('Subscribing device to topic', [
                    'device' => $endpoint['EndpointArn'],
                    'topic' => $topic,
                    'platform' => $platform
                ]);
                try {
                    $this->sns->subscribe([
                        'TopicArn' => $topic,
                        'Protocol' => 'application',
                        'Endpoint' => $endpoint['EndpointArn']
                    ]);
                } catch (SnsException $e) {
                    $this->logger && $this->logger->info('Error subscribing device to topic', [
                        'device' => $endpoint['EndpointArn'],
                        'topic' => $topic,
                        'platform' => $platform,
                        'exception' => $e
                    ]);
                }
            }
        }
    }
}
