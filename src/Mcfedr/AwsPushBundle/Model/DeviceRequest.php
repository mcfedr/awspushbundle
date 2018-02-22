<?php

namespace Mcfedr\AwsPushBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

class DeviceRequest
{
    /**
     * @var Device
     * @Assert\NotBlank()
     * @Assert\Valid()
     */
    private $device;

    /**
     * @return Device
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param Device $device
     */
    public function setDevice(Device $device = null)
    {
        $this->device = $device;
    }
}
