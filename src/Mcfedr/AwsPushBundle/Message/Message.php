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
    const ADM_MAX_LENGTH = 6144;

    const PLATFORM_GCM = 'gcm';
    const PLATFORM_APNS = 'apns';
    const PLATFORM_ADM = 'adm';

    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';

    /**
     * The text is automatically trimmed when sending to APNS and GCM
     * The text will be sent to GCM and ADM as 'message' in the data field.
     *
     * @var string
     */
    private $text;

    /**
     * This is notification priority for GCM should be 'high' or 'normal'. High priority is default.
     *
     * @var string
     */
    private $priority = self::PRIORITY_HIGH;

    /**
     * The key of a localized string that will form the message displayed.
     *
     * @var string
     */
    private $localizedKey;

    /**
     * Arguments for the localized message
     * If you are using iOS these should be strings only, plural localization doesn't work!
     *
     * @var array
     */
    private $localizedArguments;

    /**
     * If set then the text content of the message will be trimmed where necessary to fit in the length limits of each
     * platform.
     *
     * @var bool
     *
     * @see Message::APNS_MAX_LENGTH
     * @see Message::GCM_MAX_LENGTH
     */
    private $allowTrimming = true;

    /**
     * APNS only.
     *
     * @var int
     */
    private $badge;

    /**
     * APNS only
     * Including this key means that when your app is launched in the background or resumed.
     *
     * @var bool
     */
    private $contentAvailable;

    /**
     * APNS only.
     *
     * @var string
     */
    private $sound;

    /**
     * This is the data to send to all services, it will be deep merged with the other data.
     *
     * @var array
     */
    private $custom = [];

    /**
     * If set, will be sent to GCM, deep merged with ['message' => $text] in the data field.
     *
     * @var array
     */
    private $gcmData;

    /**
     * If set, will be sent to ADM, deep merged with ['message' => $text] in the data field.
     *
     * @var array
     */
    private $admData;

    /**
     * If set, will be sent to APNS, deep merged as the top level.
     *
     * @var array
     */
    private $apnsData;

    /**
     * GCM and ADM only.
     *
     * @var string
     */
    private $collapseKey = self::NO_COLLAPSE;

    /**
     * GCM and ADM only.
     *
     * GCM: default is 4 weeks
     * ADM: default is 1 week
     *
     * @var int number of seconds that the server should retain the message
     */
    private $ttl;

    /**
     * GCM only.
     *
     * @var bool
     */
    private $delayWhileIdle = false;

    /**
     * Platforms that this message will create JSON for, and throw errors for.
     *
     * @var array
     *
     * @see Message::PLATFORM_GCM
     * @see Message::PLATFORM_APNS
     * @see Message::PLATFORM_ADM
     */
    private $platforms = [self::PLATFORM_GCM, self::PLATFORM_APNS, self::PLATFORM_ADM];

    /**
     * @param string $text
     */
    public function __construct($text = null)
    {
        $this->text = $text;
    }

    /**
     * Set the number on displayed badge
     * APNS only.
     *
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
     * @return bool
     */
    public function isContentAvailable()
    {
        return $this->contentAvailable;
    }

    /**
     * Including this key means that when your app is launched in the background or resumed
     * APNS only.
     *
     * @param bool $contentAvailable
     */
    public function setContentAvailable($contentAvailable)
    {
        $this->contentAvailable = $contentAvailable;
    }

    /**
     * This is the data to send to all services, it will be deep merged with the other data.
     *
     * @param array $custom
     */
    public function setCustom(array $custom)
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
     * Name of sound file to use
     * APNS only.
     *
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
     * The text is automatically trimmed when sending to APNS and GCM
     * The text will be sent to GCM and ADM as 'message' in the data field.
     *
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
     * Set Priority.
     *
     * @param string $priority
     *
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Get Priority.
     *
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return string
     */
    public function getLocalizedKey()
    {
        return $this->localizedKey;
    }

    /**
     * The key of a localized string that will form the message displayed.
     *
     * @param string $localizedKey
     *
     * @return Message
     */
    public function setLocalizedKey($localizedKey)
    {
        $this->localizedKey = $localizedKey;

        return $this;
    }

    /**
     * @return array
     */
    public function getLocalizedArguments()
    {
        return $this->localizedArguments;
    }

    /**
     * Arguments for the localized message
     * If you are using iOS these should be strings only, plural localization doesn't work!
     *
     * @param array $localizedArguments
     *
     * @return Message
     */
    public function setLocalizedArguments(array $localizedArguments = null)
    {
        $this->localizedArguments = $localizedArguments;

        return $this;
    }

    /**
     * Convenience to set localized text.
     *
     * @param string     $key
     * @param array|null $arguments
     */
    public function setLocalizedText($key, array $arguments = null)
    {
        $this->setLocalizedKey($key);
        $this->setLocalizedArguments($arguments);
    }

    /**
     * @return bool
     */
    public function getAllowTrimming()
    {
        return $this->allowTrimming;
    }

    /**
     * If set then the text content of the message will be trimmed where necessary to fit in the length limits of each
     * platform.
     *
     * @param bool $allowTrimming
     */
    public function setAllowTrimming($allowTrimming)
    {
        $this->allowTrimming = $allowTrimming;
    }

    /**
     * GCM and ADM only.
     *
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
     * GCM only.
     *
     * @param bool $delayWhileIdle
     */
    public function setDelayWhileIdle($delayWhileIdle)
    {
        $this->delayWhileIdle = $delayWhileIdle;
    }

    /**
     * @return bool
     */
    public function getDelayWhileIdle()
    {
        return $this->delayWhileIdle;
    }

    /**
     * number of seconds that the server should retain the message.
     *
     * GCM and ADM only
     *
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
     * If set, will be sent to GCM, deep merged with ['message' => $text] in the data field.
     *
     * @param array $gcmData
     */
    public function setGcmData(array $gcmData)
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
     * If set, will be sent to ADM, deep merged with ['message' => $text] in the data field.
     *
     * @param array $admData
     *
     * @return Message
     */
    public function setAdmData(array $admData)
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
     * If set, will be sent to APNS, deep merged as the top level, meaning you can add extra data to 'aps'.
     *
     * @param array $apnsData
     *
     * @return Message
     */
    public function setApnsData(array $apnsData)
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
     * Platforms that this message will create JSON for, and throw errors for (for long messages).
     *
     * @param array $platforms
     *
     * @see Message::PLATFORM_GCM
     * @see Message::PLATFORM_APNS
     * @see Message::PLATFORM_ADM
     */
    public function setPlatforms(array $platforms)
    {
        $this->platforms = $platforms;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [
            'default' => $this->text
        ];

        if (in_array(self::PLATFORM_APNS, $this->platforms)) {
            $data['APNS'] = $data['APNS_SANDBOX'] = $this->getApnsJson();
        }

        if (in_array(self::PLATFORM_GCM, $this->platforms)) {
            $data['GCM'] = $this->getGcmJson();
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
     * Get the json to send via Apple Push Notification Server
     * For APNS the max length applies to the whole message.
     *
     * @return string
     *
     * @throws MessageTooLongException
     */
    private function getApnsJson()
    {
        return json_encode($this->getTrimmedJson([$this, 'getApnsJsonInner'], static::APNS_MAX_LENGTH, 'You message for APNS is too long'), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the correct apple push notification server data.
     *
     * @param string $text
     *
     * @return array
     */
    private function getApnsJsonInner($text)
    {
        $apns = [
            'aps' => []
        ];

        if (!is_null($this->localizedKey)) {
            $apns['aps']['alert'] = [
                'loc-key' => $this->localizedKey
            ];
            if ($this->localizedArguments) {
                $apns['aps']['alert']['loc-args'] = $this->localizedArguments;
            }
        } elseif (!is_null($text)) {
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

        // Force aps to be an object, because it shouldnt get encoded as [] but as {}
        if (!count($merged['aps'])) {
            $merged['aps'] = new \stdClass();
        }

        return $merged;
    }

    /**
     * Get the json to send via Amazon Device Messaging.
     *
     * @return string
     */
    private function getAdmJson()
    {
        $adm = [
            'data' => $this->getTrimmedJson([$this, 'getAdmJsonInner'], static::ADM_MAX_LENGTH, 'You message for ADM is too long'),
            'expiresAfter' => $this->ttl
        ];

        foreach ($adm['data'] as $key => $value) {
            if (!is_string($value)) {
                $adm['data']["{$key}_json"] = json_encode($value);
                unset($adm['data'][$key]);
            }
        }

        if ($this->collapseKey != static::NO_COLLAPSE) {
            $adm['consolidationKey'] = $this->collapseKey;
        }

        return json_encode($adm, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Gets the data part of the GCM message.
     *
     * @param $text
     *
     * @return array
     */
    private function getAdmJsonInner($text)
    {
        $data = $this->getAndroidJsonInner($text);

        $merged = $this->arrayMergeDeep($data, $this->custom, $this->admData ? $this->admData : []);

        // Force to be an object, because it shouldnt get encoded as [] but as {}
        if (!count($merged)) {
            $merged = new \stdClass();
        }

        return $merged;
    }

    /**
     * Get the json to send via Google Cloud Messaging
     * For GCM the max length is for the data field only.
     *
     * @return string
     *
     * @throws MessageTooLongException
     */
    private function getGcmJson()
    {
        return json_encode([
            'collapse_key' => $this->collapseKey,
            'time_to_live' => $this->ttl,
            'delay_while_idle' => $this->delayWhileIdle,
            'priority' => $this->priority,
            'data' => $this->getTrimmedJson([$this, 'getGcmJsonInner'], static::GCM_MAX_LENGTH, 'You message for GCM is too long')
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Gets the data part of the GCM message.
     *
     * @param $text
     *
     * @return array
     */
    private function getGcmJsonInner($text)
    {
        $data = $this->getAndroidJsonInner($text);

        $merged = $this->arrayMergeDeep($data, $this->custom, $this->gcmData ? $this->gcmData : []);

        // Force to be an object, because it shouldnt get encoded as [] but as {}
        if (!count($merged)) {
            $merged = new \stdClass();
        }

        return $merged;
    }

    /**
     * Gets the base of the data for the android platforms, with text and localization keys.
     *
     * @param $text
     *
     * @return array
     */
    private function getAndroidJsonInner($text)
    {
        $data = [];
        if (!is_null($text)) {
            $data['message'] = $text;
        }

        if (!is_null($this->localizedKey)) {
            $data['message-loc-key'] = $this->localizedKey;
            if ($this->localizedArguments) {
                $data['message-loc-args'] = $this->localizedArguments;
            }
        }

        return $data;
    }

    /**
     * Using a inner function gets the data, and trys again if its too long by trimming the text.
     *
     * @param callable $inner
     * @param int      $limit
     * @param string   $error
     *
     * @return array
     *
     * @throws MessageTooLongException
     */
    private function getTrimmedJson(callable $inner, $limit, $error)
    {
        $gcmInner = $inner($this->text);
        $gcmInnerJson = json_encode($gcmInner, JSON_UNESCAPED_UNICODE);
        if (($gcmInnerJsonLength = strlen($gcmInnerJson)) > $limit) {
            $cut = $gcmInnerJsonLength - $limit;
            //Note that strlen returns the byte length of the string
            $textLength = strlen($this->text);
            if ($textLength > $cut && $this->allowTrimming) {
                $gcmInner = $inner(mb_strcut($this->text, 0, $textLength - $cut - 3, 'utf8') . '...');
            } else {
                throw new MessageTooLongException("$error $gcmInnerJson");
            }
        }

        return $gcmInner;
    }

    /**
     * Merge arrays, deeply.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    private function arrayMergeDeep(array $array1, array $array2)
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
