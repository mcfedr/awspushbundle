<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Model;

use Symfony\Component\Validator\Constraints\NotBlank;

class Device
{
    /**
     * @NotBlank()
     */
    private string $deviceId;

    /**
     * @NotBlank()
     */
    private string $platform;

    public function getDeviceId(): string
    {
        return $this->deviceId;
    }

    public function setDeviceId(string $deviceId): self
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }
}
