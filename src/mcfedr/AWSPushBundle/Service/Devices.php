<?php

namespace mcfedr\AWSPushBundle\Service;

use Aws\Sns\SnsClient;
use mcfedr\AWSPushBundle\Exception\PlatformNotConfiguredException;

class Devices
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
     * @param SnsClient $client
     * @param array $platformARNS
     */
    public function __construct(SnsClient $client, $platformARNS)
    {
        $this->sns = $client;
        $this->arns = $platformARNS;
    }

    /**
     * Register a device token
     *
     * @param string $deviceId device token
     * @param string $platform platform on which to register
     * @return string the endpoint ARN for this device
     * @throws \mcfedr\AWSPushBundle\Exception\PlatformNotConfiguredException
     */
    public function registerDevice($deviceId, $platform)
    {
        if (!isset($this->arns[$platform])) {
            throw new PlatformNotConfiguredException("There is no configured ARN for $platform");
        }

        $res = $this->sns->CreatePlatformEndpoint(
            [
                'PlatformApplicationArn' => $this->arns[$platform],
                'Token' => $deviceId
            ]
        );

        return $res['EndpointArn'];
    }

    /**
     * Unregister a device, using its endpoint ARN
     *
     * @param string $endpoint
     */
    public function unregisterDevice($endpoint)
    {
        $this->sns->deleteEndpoint(
            [
                'EndpointArn' => $endpoint
            ]
        );
    }

    /**
     * Returns a list of configured platforms
     *
     * @return array
     */
    public function validPlatforms()
    {
        return array_keys($this->arns);
    }
}
