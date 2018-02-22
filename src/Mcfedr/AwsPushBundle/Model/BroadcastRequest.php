<?php

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

    /**
     * @return Broadcast
     */
    public function getBroadcast()
    {
        return $this->broadcast;
    }

    /**
     * @param Broadcast $broadcast
     */
    public function setBroadcast(Broadcast $broadcast = null)
    {
        $this->broadcast = $broadcast;
    }
}
