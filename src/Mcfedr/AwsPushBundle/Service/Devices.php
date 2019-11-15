<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Service;

use Aws\Sns\Exception\SnsException;
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

    public function __construct(SnsClient $client, array $platformARNS)
    {
        $this->sns = $client;
        $this->arns = $platformARNS;
    }

    /**
     * Register a device token.
     *
     * @param string $deviceId device token
     * @param string $platform platform on which to register
     * @param string $userData Arbitrary user data to associate with the endpoint
     *
     * @return string the endpoint ARN for this device
     *
     * @throws PlatformNotConfiguredException
     * @throws \Exception
     */
    public function registerDevice(string $deviceId, string $platform, ?string $userData = null): string
    {
        if (!isset($this->arns[$platform])) {
            throw new PlatformNotConfiguredException("There is no configured ARN for $platform");
        }

        try {
            $args = [
                'PlatformApplicationArn' => $this->arns[$platform],
                'Token' => $deviceId,
                'Attributes' => [
                    'Enabled' => 'true',
                ],
            ];

            if ($userData) {
                $args['CustomUserData'] = $userData;
            }

            $res = $this->sns->createPlatformEndpoint($args);
        } catch (SnsException $e) {
            preg_match('/Endpoint (.+?) already/', $e->getMessage(), $matches);
            if (isset($matches[1])) {
                $this->sns->setEndpointAttributes(
                    [
                        'EndpointArn' => $matches[1],
                        'Attributes' => [
                            'Enabled' => 'true',
                        ],
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
     * Unregister a device, using its endpoint ARN.
     */
    public function unregisterDevice(string $endpoint)
    {
        $this->sns->deleteEndpoint(
            [
                'EndpointArn' => $endpoint,
            ]
        );
    }

    /**
     * Returns a list of configured platforms.
     */
    public function validPlatforms(): array
    {
        return array_keys($this->arns);
    }
}
