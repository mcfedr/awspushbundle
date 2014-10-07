<?php
namespace Mcfedr\AwsPushBundle\Topic;

/**
 * Represents a member of a topic group
 *
 * @package Mcfedr\AwsPushBundle\Topic
 */
class Topic implements \JsonSerializable
{

    /**
     * @var string
     */
    private $arn;

    /**
     * @var int
     */
    private $number;

    /**
     * @var string
     */
    private $name;

    /**
     * @param string $number
     * @param int $arn
     * @param string $name
     */
    public function __construct($number, $arn, $name)
    {
        $this->number = $number;
        $this->arn = $arn;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getArn()
    {
        return $this->arn;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function jsonSerialize()
    {
        return [
            'number' => $this->getNumber(),
            'arn' => $this->getArn(),
            'name' => $this->getName()
        ];
    }
}
