<?php

namespace Mcfedr\AwsPushBundle\Message;

use Mcfedr\AwsPushBundle\Exception\MessageTooLongException;

class Message implements \JsonSerializable
{
    const GCM_NO_COLLAPSE = 'do_not_collapse';
    const APNS_MAX_LENGTH = 2048;
    const GCM_MAX_LENGTH = 4096;

    /**
     * The text is automatically trimmed when sending to APNS
     * The text will only be sent to GCM and ADM if no platform specific data is set
     *
     * @var string
     */
    private $text;

    /**
     * APNS only
     *
     * @var int
     */
    private $badge;

    /**
     * APNS only
     * Provide this key with a value of 1 to indicate that new content is available. Including this key and value means that when your app is launched in the background or resumed
     *
     * @var bool
     */
    private $contentAvailable;

    /**
     * APNS only
     *
     * @var string
     */
    private $sound;

    /**
     * This is the data to send to all services, it will be deep merged with the other data
     *
     * @var array
     */
    private $custom = [];

    /**
     * If set, will be sent to GCM, otherwise ['message' => $text] will be sent as part of the data
     *
     * @var array
     */
    private $gcmData;

    /**
     * If set, will be sent to ADM, otherwise ['message' => $text] will be sent as part of the data
     *
     * @var array
     */
    private $admData;

    /**
     * If set, will be sent to APNS
     *
     * @var array
     */
    private $apnsData;

    /**
     * GCM and ADM only
     *
     * @var string
     */
    private $collapseKey = self::GCM_NO_COLLAPSE;

    /**
     * GCM only
     *
     * @var int
     */
    private $ttl;

    /**
     * GCM only
     *
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
     * @return boolean
     */
    public function isContentAvailable()
    {
        return $this->contentAvailable;
    }

    /**
     * @param boolean $contentAvailable
     */
    public function setContentAvailable($contentAvailable)
    {
        $this->contentAvailable = $contentAvailable;
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

    /**
     * @param array $admData
     * @return Message
     */
    public function setAdmData($admData)
    {
        $this->admData = $admData;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdmData()
    {
        return $this->admData;
    }

    /**
     * @param array $apnsData
     * @return Message
     */
    public function setApnsData($apnsData)
    {
        $this->apnsData = $apnsData;
        return $this;
    }

    /**
     * @return array
     */
    public function getApnsData()
    {
        return $this->apnsData;
    }

    public function jsonSerialize()
    {
        $apnsData = $this->getApnsJson($this->text);
        if (($apnsDataLength = strlen($apnsData)) > static::APNS_MAX_LENGTH) {
            $cut = $apnsDataLength - static::APNS_MAX_LENGTH;
            //Note that strlen returns the byte length of the string
            $textLength = strlen($this->text);
            if ($textLength > $cut) {
                $apnsData = $this->getApnsJson(mb_strcut($this->text, 0, $textLength - $cut - 3, 'utf8') . '...');
            } else {
                throw new MessageTooLongException("You message for APNS is too long $apnsData");
            }
        }

        $gcmData = $this->getGcmJson($this->text);
        if (($gcmDataLength = strlen($gcmData)) > static::GCM_MAX_LENGTH) {
            $cut = $gcmDataLength - static::GCM_MAX_LENGTH;
            //Note that strlen returns the byte length of the string
            $textLength = strlen($this->text);
            if ($textLength > $cut) {
                $gcmData = $this->getGcmJson(mb_strcut($this->text, 0, $textLength - $cut - 3, 'utf8') . '...');
            } else {
                throw new MessageTooLongException("You message for GCM is too long $gcmData");
            }
        }

        return [
            'default' => $this->text,
            'APNS' => $apnsData,
            'APNS_SANDBOX' => $apnsData,
            'ADM' => $this->getAdmJson(),
            'GCM' => $gcmData
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
    private function getApnsJson($text)
    {
        $apns = [
            'aps' => []
        ];

        if (!is_null($text)) {
            $apns['aps']['alert'] = $text;
        }

        if ($this->isContentAvailable()) {
            $apns['aps']['content-available'] = 1;
        }

        if (!is_null($this->badge)) {
            $apns['aps']['badge'] = $this->badge;
        }

        if (!is_null($this->sound)) {
            $apns['aps']['sound'] = $this->sound;
        }

        return json_encode(
            $this->arrayMergeDeep($apns, $this->custom, $this->apnsData ? $this->apnsData : []),
            JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Get the json to send via Amazon Device Messaging
     *
     * @return string
     */
    private function getAdmJson()
    {
        $adm = [
            'data' => $this->arrayMergeDeep($this->admData ? $this->admData : ['message' => $this->text], $this->custom)
        ];

        if ($this->collapseKey != static::GCM_NO_COLLAPSE) {
            $adm['consolidationKey'] = $this->collapseKey;
        }

        return json_encode($adm, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the json to send via Google Cloud Messaging
     *
     * @param string $text
     * @return string
     */
    private function getGcmJson($text)
    {
        return json_encode(
            [
                'collapse_key' => $this->collapseKey,
                'time_to_live' => $this->ttl,
                'delay_while_idle' => $this->delayWhileIdle,
                'data' => $this->arrayMergeDeep($this->gcmData ? $this->gcmData : ['message' => $text], $this->custom)
            ],
            JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * Merge arrays, deeply
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function arrayMergeDeep($array1, $array2)
    {
        $result = [];
        foreach (func_get_args() as $array) {
            foreach ($array as $key => $value) {
                // Renumber integer keys as array_merge_recursive() does. Note that PHP
                // automatically converts array keys that are integer strings (e.g., '1')
                // to integers.
                if (is_integer($key)) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    // Recurse when both values are arrays.
                    $result[$key] = $this->arrayMergeDeep($result[$key], $value);
                } else {
                    // Otherwise, use the latter value, overriding any previous value.
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
}
