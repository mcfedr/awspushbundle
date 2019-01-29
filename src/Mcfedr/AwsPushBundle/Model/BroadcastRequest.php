<?php

declare(strict_types=1);

namespace Mcfedr\AwsPushBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

class BroadcastRequest
{
    /**
     * @var Broadcast
     * @Assert\NotBlank()
     * @Assert\Valid()
     */
    private $broadcast;

    public function getBroadcast(): Broadcast
    {
        return $this->broadcast;
    }

    public function setBroadcast(Broadcast $broadcast): self
    {
        $this->broadcast = $broadcast;

        return $this;
    }
}
