<?php

namespace Mcfedr\AwsPushBundle\Message;

use Mcfedr\AwsPushBundle\Exception\MessageTooLongException;

class Message implements \JsonSerializable
{
    /**
     * @deprecated use NO_COLLAPSE
     * @see NO_COLLAPSE
     */
    const GCM_NO_COLLAPSE = 'do_not_collapse';

    const NO_COLLAPSE = 'do_not_collapse';

    const APNS_MAX_LENGTH = 2048;

    const GCM_MAX_LENGTH = 4096;

    const PLATFORM_GCM = 'gcm';
    const PLATFORM_APNS = 'apns';
    const PLATFORM_ADM = 'adm';

    /**
     * The text is automatically trimmed when sending to APNS and GCM
     * The text will only be sent to GCM and ADM as 'message' in the data field
     *
     * @var string
     */
    private $text;

    /**
     * @var bool
     */
    private $allowTrimming = true;

    /**
     * APNS only
     *
     * @var int
     */
    private $badge;

    /**
     * APNS only
     * Including this key means that when your app is launched in the background or resumed
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
     * If set, will be sent to GCM, deep merged with ['message' => $text] in the data field
     *
     * @var array
     */
    private $gcmData;

    /**
     * If set, will be sent to ADM, deep merged with ['message' => $text] in the data field
     *
     * @var array
     */
    private $admData;

    /**
     * If set, will be sent to APNS, deep merged as the top level
     *
     * @var array
     */
    private $apnsData;

    /**
     * GCM and ADM only
     *
     * @var string
     */
    private $collapseKey = self::NO_COLLAPSE;

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
     * Platforms that this message will create JSON for, and throw errors for
     *
     * @var array
     */
    private $platforms = [self::PLATFORM_GCM, self::PLATFORM_APNS, self::PLATFORM_GCM];

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
     * @return boolean
     */
    public function getAllowTrimming()
    {
        return $this->allowTrimming;
    }

    /**
     * @param boolean $allowTrimming
     */
    public function setAllowTrimming($allowTrimming)
    {
        $this->allowTrimming = $allowTrimming;
    }

    /**
     * @param string $collapseKey
     */
    public function setCollapseKey($collapseKey)
    {
        $this->collapseKey = $collapseKey ?: static::NO_COLLAPSE;
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

    /**
     * @return array
     */
    public function getPlatforms()
    {
        return $this->platforms;
    }

    /**
     * @param array $platforms
     * @see self::PLATFORM_GCM
     * @see self::PLATFORM_APNS
     * @see self::PLATFORM_ADM
     */
    public function setPlatforms(array $platforms)
    {
        $this->platforms = $platforms;
    }

    public function jsonSerialize()
    {
        $data = [
            'default' => $this->text
        ];

        if (in_array(self::PLATFORM_APNS, $this->platforms)) {
            $apnsData = $this->getApnsJson($this->text);
            if (($apnsDataLength = strlen($apnsData)) > static::APNS_MAX_LENGTH) {
                $cut = $apnsDataLength - static::APNS_MAX_LENGTH;
                //Note that strlen returns the byte length of the string
                $textLength = strlen($this->text);
                if ($textLength > $cut && $this->allowTrimming) {
                    $apnsData = $this->getApnsJson(mb_strcut($this->text, 0, $textLength - $cut - 3, 'utf8') . '...');
                } else {
                    throw new MessageTooLongException("You message for APNS is too long $apnsData");
                }
            }
            $data['APNS'] = $data['APNS_SANDBOX'] = $apnsData;
        }

        if (in_array(self::PLATFORM_GCM, $this->platforms)) {
            $gcmData = $this->getGcmJson($this->text);
            if (($gcmDataLength = strlen($gcmData)) > static::GCM_MAX_LENGTH) {
                $cut = $gcmDataLength - static::GCM_MAX_LENGTH;
                //Note that strlen returns the byte length of the string
                $textLength = strlen($this->text);
                if ($textLength > $cut && $this->allowTrimming) {
                    $gcmData = $this->getGcmJson(mb_strcut($this->text, 0, $textLength - $cut - 3, 'utf8') . '...');
                } else {
                    throw new MessageTooLongException("You message for GCM is too long $gcmData");
                }
            }
            $data['GCM'] = $gcmData;
        }

        if (in_array(self::PLATFORM_ADM, $this->platforms)) {
            $data['ADM'] = $this->getAdmJson();
        }

        return $data;
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

        $merged = $this->arrayMergeDeep($apns, $this->custom, $this->apnsData ? $this->apnsData : []);

        // Force aps to be an object, after the merge so that keys from custom or apnsData can get in
        if (!count($merged['aps'])) {
            $merged['aps'] = new \stdClass();
        }

        return json_encode($merged, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the json to send via Amazon Device Messaging
     *
     * @return string
     */
    private function getAdmJson()
    {
        $data = [];
        if (!is_null($this->text)) {
            $data['message'] = $this->text;
        }

        $merged = $this->arrayMergeDeep($data, $this->custom, $this->admData ? $this->admData : []);

        if (!count($merged)) {
            $merged = new \stdClass();
        }

        $adm = [
            'data' => $merged
        ];

        if ($this->collapseKey != static::NO_COLLAPSE) {
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
        $data = [];
        if (!is_null($text)) {
            $data['message'] = $text;
        }

        $merged = $this->arrayMergeDeep($data, $this->custom, $this->gcmData ? $this->gcmData : []);

        if (!count($merged)) {
            $merged = new \stdClass();
        }

        return json_encode([
            'collapse_key' => $this->collapseKey,
            'time_to_live' => $this->ttl,
            'delay_while_idle' => $this->delayWhileIdle,
            'data' => $merged
        ], JSON_UNESCAPED_UNICODE);
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
