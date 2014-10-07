<?php

namespace Mcfedr\AwsPushBundle\Service;

use Aws\Sns\Exception\InvalidParameterException;
use Aws\Sns\SnsClient;
use Mcfedr\AwsPushBundle\Exception\PlatformNotConfiguredException;

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
     * @throws PlatformNotConfiguredException
     */
    public function registerDevice($deviceId, $platform)
    {
        if (!isset($this->arns[$platform])) {
            throw new PlatformNotConfiguredException("There is no configured ARN for $platform");
        }

        try {
            $res = $this->sns->createPlatformEndpoint(
                [
                    'PlatformApplicationArn' => $this->arns[$platform],
                    'Token' => $deviceId,
                    'Attributes' => [
                        'Enabled' => 'true'
                    ]
                ]
            );
        } catch (InvalidParameterException $e) {
            preg_match('/Endpoint (.+?) already/', $e->getMessage(), $matches);
            if (isset($matches[1])) {
                $this->sns->setEndpointAttributes(
                    [
                        'EndpointArn' => $matches[1],
                        'Attributes' => [
                            'Enabled' => 'true'
                        ]
                    ]
                );
                return $matches[1];
            } else {
                throw $e;
            }
        }

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
