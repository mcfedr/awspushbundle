<?php

namespace Mcfedr\AwsPushBundle\Model;

use Symfony\Component\Validator\Constraints\NotBlank;

class Device
{
    /**
     * @var string
     * @NotBlank()
     */
    private $deviceId;

    /**
     * @var string
     * @NotBlank()
     */
    private $platform;

    /**
     * @return string
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * @param string $deviceId
     *
     * @return Device
     */
    public function setDeviceId($deviceId)
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @param string $platform
     *
     * @return Device
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }
}
