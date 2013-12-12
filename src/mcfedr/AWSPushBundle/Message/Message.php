<?php

namespace mcfedr\AWSPushBundle\Message;


class Message implements \JsonSerializable {
    /**
     * @var string
     */
    private $text;

    /**
     * @var int
     */
    private $badge;

    /**
     * @var string
     */
    private $sound;

    /**
     * @var array
     */
    private $custom = array();

    /**
     * @var string
     */
    private $collapseKey = 'do_not_collapse';

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var boolean
     */
    private $delayWhileIdle = false;

    public function __construct($text = null) {
        $this->text = $text;
    }

    /**
     * @param int $badge
     */
    public function setBadge($badge) {
        $this->badge = $badge;
    }

    /**
     * @return int
     */
    public function getBadge() {
        return $this->badge;
    }

    /**
     * @param array $custom
     */
    public function setCustom($custom) {
        $this->custom = $custom;
    }

    /**
     * @return array
     */
    public function getCustom() {
        return $this->custom;
    }

    /**
     * @param string $sound
     */
    public function setSound($sound) {
        $this->sound = $sound;
    }

    /**
     * @return string
     */
    public function getSound() {
        return $this->sound;
    }

    /**
     * @param string $text
     */
    public function setText($text) {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * @param string $collapseKey
     */
    public function setCollapseKey($collapseKey) {
        $this->collapseKey = $collapseKey;
    }

    /**
     * @return string
     */
    public function getCollapseKey() {
        return $this->collapseKey;
    }

    /**
     * @param boolean $delayWhileIdle
     */
    public function setDelayWhileIdle($delayWhileIdle) {
        $this->delayWhileIdle = $delayWhileIdle;
    }

    /**
     * @return boolean
     */
    public function getDelayWhileIdle() {
        return $this->delayWhileIdle;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl) {
        $this->ttl = $ttl;
    }

    /**
     * @return int
     */
    public function getTtl() {
        return $this->ttl;
    }

    public function jsonSerialize() {
        $apns = json_encode(array_merge([
            'aps' => [
                'alert' => $this->text,
                'badge' => $this->badge,
                'sound' => $this->sound
            ]
        ], $this->custom));

        return [
            'default' => $this->text,
            'APNS' => $apns,
            'APNS_SANDBOX' => $apns,
            'ADM' => $apns,
            'GCM' => json_encode([
                'collapse_key' => $this->collapseKey,
                'time_to_live' => $this->ttl,
                'delay_while_idle' => $this->delayWhileIdle,
                'data' => $this->custom
            ])
        ];
    }

    public function __toString() {
        return json_encode($this);
    }
} 