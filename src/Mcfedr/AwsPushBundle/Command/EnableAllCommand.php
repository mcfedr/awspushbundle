<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Command;

use Aws\Sns\SnsClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnableAllCommand extends Command
{
    protected static $defaultName = 'mcfedr:aws:enable';

    private SnsClient $sns;

    private array $arns;

    private ?LoggerInterface $logger;

    public function __construct(SnsClient $sns, array $arns, LoggerInterface $logger = null)
    {
        parent::__construct();

        $this->sns = $sns;
        $this->arns = $arns;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Reenable all devices');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->arns as $platform => $arn) {
            $this->logger && $this->logger->info("Enabling $platform");
            $this->enablePlatform($platform);
        }

        return 0;
    }

    /**
     * Enable all devices registered on platform.
     */
    private function enablePlatform(string $platform): void
    {
        foreach ($this->sns->getPaginator('ListEndpointsByPlatformApplication', [
            'PlatformApplicationArn' => $this->arns[$platform],
        ]) as $endpointsResult) {
            foreach ($endpointsResult['Endpoints'] as $endpoint) {
                if ($endpoint['Attributes']['Enabled'] == 'false') {
                    try {
                        $this->sns->setEndpointAttributes(
                            [
                                'EndpointArn' => $endpoint['EndpointArn'],
                                'Attributes' => [
                                    'Enabled' => 'true',
                                ],
                            ]
                        );
                        $this->logger && $this->logger->info("Enabled {$endpoint['EndpointArn']}");
                    } catch (\Exception $e) {
                        $this->logger && $this->logger->error(
                            "Failed to push set attributes on {$endpoint['EndpointArn']}",
                            [
                                'exception' => $e,
                                'endpoint' => $endpoint,
                            ]
                        );
                    }
                }
            }
        }
    }
}
