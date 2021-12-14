<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Model;

use Mcfedr\AwsPushBundle\Message\Message;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

class Broadcast
{
    private string $platform;

    /**
     * @Valid()
     * @NotBlank()
     */
    private Message $message;

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getPlatform(): ?string
    {
        return empty($this->platform) ? null : $this->platform;
    }

    public function setPlatform(?string $platform): self
    {
        $this->platform = $platform;

        return $this;
    }
}
