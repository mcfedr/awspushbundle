<?php

namespace mcfedr\AWSPushBundle\Message;


use mcfedr\AWSPushBundle\Exception\MessageTooLongException;

class Message implements \JsonSerializable
{
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
    private $custom = [];

    /**
     * @var array
     */
    private $gcmData;

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

    /**
     * @param string $text
     */
    public function __construct($text = null)
    {
        $this->text = $text;
    }

    /**
     * @param int $badge
     */
    public function setBadge($badge)
    {
        $this->badge = $badge;
    }

    /**
     * @return int
     */
    public function getBadge()
    {
        return $this->badge;
    }

    /**
     * @param array $custom
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;
    }

    /**
     * @return array
     */
    public function getCustom()
    {
        return $this->custom;
    }

    /**
     * @param string $sound
     */
    public function setSound($sound)
    {
        $this->sound = $sound;
    }

    /**
     * @return string
     */
    public function getSound()
    {
        return $this->sound;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $collapseKey
     */
    public function setCollapseKey($collapseKey)
    {
        $this->collapseKey = $collapseKey;
    }

    /**
     * @return string
     */
    public function getCollapseKey()
    {
        return $this->collapseKey;
    }

    /**
     * @param boolean $delayWhileIdle
     */
    public function setDelayWhileIdle($delayWhileIdle)
    {
        $this->delayWhileIdle = $delayWhileIdle;
    }

    /**
     * @return boolean
     */
    public function getDelayWhileIdle()
    {
        return $this->delayWhileIdle;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @param array $gcmData
     */
    public function setGcmData($gcmData)
    {
        $this->gcmData = $gcmData;
    }

    /**
     * @return array
     */
    public function getGcmData()
    {
        return $this->gcmData;
    }

    public function jsonSerialize()
    {
        $apnsData = $this->getApnsData($this->text);
        if (($apnsDataLength = strlen($apnsData)) > 256) {
            $cut = $apnsDataLength - 256;
            $textLength = strlen($this->text);
            if ($textLength > $cut) {
                $apnsData = $this->getApnsData(mb_strcut($this->text, 0, $textLength - $cut, 'utf8'));
            } else {
                throw new MessageTooLongException("You message for APNS is too long $apnsData");
            }
        }

        return [
            'default' => $this->text,
            'APNS' => $apnsData,
            'APNS_SANDBOX' => $apnsData,
            'ADM' => $apnsData,
            'GCM' => json_encode(
                [
                    'collapse_key' => $this->collapseKey,
                    'time_to_live' => $this->ttl,
                    'delay_while_idle' => $this->delayWhileIdle,
                    'data' => array_merge($this->gcmData ? $this->gcmData : ['text' => $this->text], $this->custom)
                ],
                JSON_UNESCAPED_UNICODE
            )
        ];
    }

    public function __toString()
    {
        return json_encode($this);
    }

    /**
     * Get the correct apple push notification server json
     *
     * @param string $text
     * @return string
     */
    private function getApnsData($text)
    {
        $apns = [
            'aps' => [
                'alert' => $text
            ]
        ];
        if ($this->badge) {
            $apns['aps']['badge'] = $this->badge;
        }
        if ($this->sound) {
            $apns['aps']['sound'] = $this->sound;
        }

        return json_encode(array_merge($apns, $this->custom), JSON_UNESCAPED_UNICODE);
    }
}
