<?php

declare(strict_types=1);

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

    public function getDevice(): Device
    {
        return $this->device;
    }

    public function setDevice(Device $device): self
    {
        $this->device = $device;

        return $this;
    }
}
