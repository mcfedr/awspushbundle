<?php
namespace mcfedr\AWSPushBundle\Command;

use Aws\Sns\SnsClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveDisabledCommand extends Command
{
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
     * @param SnsClient $sns
     * @param array $arns
     * @param \Psr\Log\LoggerInterface $logger
     */
    function __construct(SnsClient $sns, $arns, LoggerInterface $logger)
    {
        parent::__construct();

        $this->sns = $sns;
        $this->arns = $arns;
        $this->logger = $logger;
    }


    protected function configure()
    {
        $this
            ->setName('mcfedr:aws:remove')
            ->setDescription('Remove disabled devices');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->arns as $platform => $arn) {
            $this->logger->info("Removing from $platform");
            $this->removeFromPlatform($platform);
        }
    }

    /**
     * Enable all devices registered on platform
     *
     * @param string $platform
     */
    private function removeFromPlatform($platform)
    {
        foreach ($this->sns->getListEndpointsByPlatformApplicationIterator(
                     [
                         'PlatformApplicationArn' => $this->arns[$platform]
                     ]
                 ) as $endpoint) {
            if ($endpoint['Attributes']['Enabled'] == "false") {
                try {
                    $this->sns->deleteEndpoint(
                        [
                            'EndpointArn' => $endpoint['EndpointArn']
                        ]
                    );
                    $this->logger->info("Removed {$endpoint['EndpointArn']}");
                } catch (\Exception $e) {
                    $this->logger->error(
                        "Failed to remove endpoint {$endpoint['EndpointArn']}",
                        [
                            'exception' => $e,
                            'endpoint' => $endpoint
                        ]
                    );
                }
            }
        }
    }
}
