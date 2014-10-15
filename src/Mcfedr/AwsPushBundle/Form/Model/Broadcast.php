<?php


namespace Mcfedr\AwsPushBundle\Form\Model;


use Mcfedr\AwsPushBundle\Message\Message;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

class Broadcast
{
    /**
     * @var string
     */
    private $platform;

    /**
     * @var Message
     * @Valid()
     * @NotBlank()
     */
    private $message;

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Message $message
     * @return Broadcast
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return empty($this->platform) ? null : $this->platform;
    }

    /**
     * @param string $platform
     * @return Broadcast
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }
}
