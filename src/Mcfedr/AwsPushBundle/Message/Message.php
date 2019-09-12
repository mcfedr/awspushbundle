<?php

declare(strict_types=1);

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
     * @var ?string
     */
    private $text;

    /**
     * The text will be sent to FCM over GCM as 'title' in the notification field.
     *
     * @var ?string
     */
    private $title = null;

    /**
     * The key of a localized title string that will form the title displayed.
     *
     * @var ?string
     */
    private $titleLocalizedKey = null;

    /**
     * Arguments for the localized title.
     * If you are using iOS these should be strings only, plural localization doesn't work!
     *
     * @var ?array
     */
    private $titleLocalizedArguments = null;

    /**
     * Additional information that explains the purpose of the notification.
     *
     * @var ?string
     */
    private $subTitle = null;

    /**
     * The key of a localized subtitle string that will form the title displayed.
     *
     * @var ?string
     */
    private $subTitleLocalizedKey = null;

    /**
     * Arguments for the localized subtitle.
     * If you are using iOS these should be strings only, plural localization doesn't work!
     *
     * @var ?array
     */
    private $subTitleLocalizedArguments = null;

    /**
     * The notification type.
     *
     * @var ?string
     */
    private $category = null;

    /**
     * This is notification priority for GCM should be 'high' or 'normal'. High priority is default.
     *
     * @var string
     */
    private $priority = self::PRIORITY_HIGH;

    /**
     * The key of a localized string that will form the message displayed.
     *
     * @var ?string
     */
    private $localizedKey;

    /**
     * Arguments for the localized message
     * If you are using iOS these should be strings only, plural localization doesn't work!
     *
     * @var ?array
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
     * @var ?int
     */
    private $badge;

    /**
     * APNS only
     * Including this key means that when your app is launched in the background or resumed.
     *
     * @var ?bool
     */
    private $contentAvailable;

    /**
     * APNS only.
     *
     * @var ?string
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
     * @var ?array
     */
    private $gcmData;

    /**
     * If set, will be sent to ADM, deep merged with ['message' => $text] in the data field.
     *
     * @var ?array
     */
    private $admData;

    /**
     * If set, will be sent to APNS, deep merged as the top level.
     *
     * @var ?array
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
     * @var ?int number of seconds that the server should retain the message
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
     * An app-specific identifier for grouping related notifications.
     */
    private $threadId = null;

    /**
     * The notification service app extension flag.
     */
    private $mutableContent = null;

    public function __construct(?string $text = null)
    {
        $this->text = $text;
    }

    public function getBadge(): ?int
    {
        return $this->badge;
    }

    /**
     * Set the number on displayed badge
     * APNS only.
     */
    public function setBadge(?int $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    public function isContentAvailable(): ?bool
    {
        return $this->contentAvailable;
    }

    /**
     * Including this key means that when your app is launched in the background or resumed
     * APNS only.
     */
    public function setContentAvailable(?bool $contentAvailable): self
    {
        $this->contentAvailable = $contentAvailable;

        return $this;
    }

    public function getCustom(): array
    {
        return $this->custom;
    }

    /**
     * This is the data to send to all services, it will be deep merged with the other data.
     */
    public function setCustom(array $custom): self
    {
        $this->custom = $custom;

        return $this;
    }

    public function getSound(): ?string
    {
        return $this->sound;
    }

    /**
     * Name of sound file to use
     * APNS only.
     */
    public function setSound(?string $sound): self
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * The text will be sent to FCM over GCM as 'title' in the data field.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * The key of a localized string that will form the title displayed.
     */
    public function getTitleLocalizedKey(): ?string
    {
        return $this->titleLocalizedKey;
    }

    public function setTitleLocalizedKey(?string $titleLocalizedKey): self
    {
        $this->titleLocalizedKey = $titleLocalizedKey;

        return $this;
    }

    /**
     * Arguments for the localized title
     * If you are using iOS these should be strings only, plural localization doesn't work!
     */
    public function getTitleLocalizedArguments(): ?array
    {
        return $this->titleLocalizedArguments;
    }

    public function setTitleLocalizedArguments(?array $titleLocalizedArguments): self
    {
        $this->titleLocalizedArguments = $titleLocalizedArguments;

        return $this;
    }

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function setSubTitle(?string $subTitle): self
    {
        $this->subTitle = $subTitle;

        return $this;
    }

    public function getSubTitleLocalizedKey(): ?string
    {
        return $this->subTitleLocalizedKey;
    }

    public function setSubTitleLocalizedKey(?string $subTitleLocalizedKey): self
    {
        $this->subTitleLocalizedKey = $subTitleLocalizedKey;

        return $this;
    }

    public function getSubTitleLocalizedArguments(): ?string
    {
        return $this->subTitleLocalizedArguments;
    }

    public function setSubTitleLocalizedArguments(?array $subTitleLocalizedArguments): self
    {
        $this->subTitleLocalizedArguments = $subTitleLocalizedArguments;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * Set notification type.
     * This string must correspond to the identifier of one of the UNNotificationCategory objects.
     */
    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * The text is automatically trimmed when sending to APNS and GCM
     * The text will be sent to GCM and ADM as 'message' in the data field.
     */
    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get Priority.
     */
    public function getPriority(): string
    {
        return $this->priority;
    }

    /**
     * Set Priority.
     */
    public function setPriority(string $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getLocalizedKey(): ?string
    {
        return $this->localizedKey;
    }

    /**
     * The key of a localized string that will form the message displayed.
     */
    public function setLocalizedKey(?string $localizedKey): self
    {
        $this->localizedKey = $localizedKey;

        return $this;
    }

    public function getLocalizedArguments(): ?array
    {
        return $this->localizedArguments;
    }

    /**
     * Arguments for the localized message
     * If you are using iOS these should be strings only, plural localization doesn't work!
     */
    public function setLocalizedArguments(?array $localizedArguments = null): self
    {
        $this->localizedArguments = $localizedArguments;

        return $this;
    }

    /**
     * Convenience to set localized text.
     */
    public function setLocalizedText(?string $key, ?array $arguments = null): self
    {
        $this->setLocalizedKey($key);
        $this->setLocalizedArguments($arguments);

        return $this;
    }

    public function getAllowTrimming(): bool
    {
        return $this->allowTrimming;
    }

    /**
     * If set then the text content of the message will be trimmed where necessary to fit in the length limits of each
     * platform.
     */
    public function setAllowTrimming(bool $allowTrimming): self
    {
        $this->allowTrimming = $allowTrimming;

        return $this;
    }

    public function getCollapseKey(): string
    {
        return $this->collapseKey;
    }

    /**
     * GCM and ADM only.
     */
    public function setCollapseKey(?string $collapseKey): self
    {
        $this->collapseKey = $collapseKey ?: static::NO_COLLAPSE;

        return $this;
    }

    public function getDelayWhileIdle(): bool
    {
        return $this->delayWhileIdle;
    }

    /**
     * GCM only.
     */
    public function setDelayWhileIdle(bool $delayWhileIdle): self
    {
        $this->delayWhileIdle = $delayWhileIdle;

        return $this;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    /**
     * number of seconds that the server should retain the message.
     *
     * GCM and ADM only
     */
    public function setTtl(?int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function getGcmData(): ?array
    {
        return $this->gcmData;
    }

    /**
     * If set, will be sent to GCM, deep merged with ['message' => $text] in the data field.
     */
    public function setGcmData(?array $gcmData): self
    {
        $this->gcmData = $gcmData;

        return $this;
    }

    public function getAdmData(): ?array
    {
        return $this->admData;
    }

    /**
     * If set, will be sent to ADM, deep merged with ['message' => $text] in the data field.
     */
    public function setAdmData(?array $admData): self
    {
        $this->admData = $admData;

        return $this;
    }

    public function getApnsData(): ?array
    {
        return $this->apnsData;
    }

    /**
     * If set, will be sent to APNS, deep merged as the top level, meaning you can add extra data to 'aps'.
     */
    public function setApnsData(?array $apnsData): self
    {
        $this->apnsData = $apnsData;

        return $this;
    }

    public function getPlatforms(): ?array
    {
        return $this->platforms;
    }

    /**
     * Platforms that this message will create JSON for, and throw errors for (for long messages).
     *
     * @see Message::PLATFORM_GCM
     * @see Message::PLATFORM_APNS
     * @see Message::PLATFORM_ADM
     */
    public function setPlatforms(array $platforms): self
    {
        $this->platforms = $platforms;

        return $this;
    }

    public function getThreadId(): ?string
    {
        return $this->threadId;
    }

    public function setThreadId(?string $threadId): self
    {
        $this->threadId = $threadId;

        return $this;
    }

    public function getMutableContent(): ?int
    {
        return $this->mutableContent;
    }

    public function setMutableContent(?int $mutableContent): self
    {
        $this->mutableContent = $mutableContent;

        return $this;
    }

    public function jsonSerialize()
    {
        $data = [
            'default' => $this->text,
        ];

        if (\in_array(self::PLATFORM_APNS, $this->platforms)) {
            $data['APNS'] = $data['APNS_SANDBOX'] = $data['APNS_VOIP_SANDBOX'] = $data['APNS_VOIP'] = $this->getApnsJson();
        }

        if (\in_array(self::PLATFORM_GCM, $this->platforms)) {
            $data['GCM'] = $this->getGcmJson();
        }

        if (\in_array(self::PLATFORM_ADM, $this->platforms)) {
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
     * @throws MessageTooLongException
     */
    private function getApnsJson(): string
    {
        return json_encode($this->getTrimmedJson([$this, 'getApnsJsonInner'], static::APNS_MAX_LENGTH, 'You message for APNS is too long'), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the correct apple push notification server data.
     */
    private function getApnsJsonInner(?string $text): array
    {
        $apns = [
            'aps' => [],
        ];

        if (null !== $this->localizedKey) {
            $apns['aps']['alert'] = [
                'loc-key' => $this->localizedKey,
            ];
            if ($this->localizedArguments) {
                $apns['aps']['alert']['loc-args'] = $this->localizedArguments;
            }
        } elseif (null !== $text) {
            $apns['aps']['alert']['body'] = $text;
        }

        if (null !== $this->title) {
            $apns['aps']['alert']['title'] = $this->title;
        } elseif (null !== $this->titleLocalizedKey) {
            $apns['aps']['alert']['title-loc-key'] = $this->titleLocalizedKey;
            if (null !== $this->titleLocalizedArguments) {
                $apns['aps']['alert']['title-loc-args'] = $this->titleLocalizedArguments;
            }
        }

        if (null !== $this->subTitle) {
            $apns['aps']['alert']['subtitle'] = $this->subTitle;
        } elseif (null !== $this->subTitleLocalizedKey) {
            $apns['aps']['alert']['subtitle-loc-key'] = $this->subTitleLocalizedKey;
            if (null !== $this->subTitleLocalizedArguments) {
                $apns['aps']['alert']['subtitle-loc-args'] = $this->subTitleLocalizedArguments;
            }
        }

        if (null !== $this->category) {
            $apns['aps']['category'] = $this->category;
        }

        if ($this->isContentAvailable()) {
            $apns['aps']['content-available'] = 1;
        }

        if (null !== $this->badge) {
            $apns['aps']['badge'] = $this->badge;
        }

        if (null !== $this->sound) {
            $apns['aps']['sound'] = $this->sound;
        }

        if (null !== $this->threadId) {
            $apns['aps']['thread-id'] = $this->threadId;
        }

        if (null !== $this->mutableContent) {
            $apns['aps']['mutable-content'] = $this->mutableContent;
        }

        $merged = $this->arrayMergeDeep($apns, $this->custom, $this->apnsData ? $this->apnsData : []);

        // Force aps to be an object, because it shouldnt get encoded as [] but as {}
        if (!\count($merged['aps'])) {
            $merged['aps'] = new \stdClass();
        }

        return $merged;
    }

    /**
     * Get the json to send via Amazon Device Messaging.
     */
    private function getAdmJson(): string
    {
        $adm = [
            'data' => $this->getTrimmedJson([$this, 'getAdmJsonInner'], static::ADM_MAX_LENGTH, 'You message for ADM is too long'),
            'expiresAfter' => $this->ttl,
        ];

        foreach ($adm['data'] as $key => $value) {
            if (!\is_string($value)) {
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
     */
    private function getAdmJsonInner(?string $text): array
    {
        $data = $this->getAndroidJsonInner($text);

        $merged = $this->arrayMergeDeep($data, $this->custom, $this->admData ? $this->admData : []);

        // Force to be an object, because it shouldnt get encoded as [] but as {}
        if (!\count($merged)) {
            $merged = new \stdClass();
        }

        return $merged;
    }

    /**
     * Get the json to send via Google Cloud Messaging
     * For GCM the max length is for the data field only.
     *
     * @throws MessageTooLongException
     */
    private function getGcmJson(): string
    {
        return json_encode([
            'collapse_key' => $this->collapseKey,
            'time_to_live' => $this->ttl,
            'delay_while_idle' => $this->delayWhileIdle,
            'priority' => $this->priority,
            'data' => $this->getTrimmedJson([$this, 'getGcmJsonInner'], static::GCM_MAX_LENGTH, 'You message for GCM is too long'),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Gets the data part of the GCM message.
     */
    private function getGcmJsonInner(?string $text): array
    {
        $data = $this->getAndroidJsonInner($text);

        $merged = $this->arrayMergeDeep($data, $this->custom, $this->gcmData ? $this->gcmData : []);

        // Force to be an object, because it shouldnt get encoded as [] but as {}
        if (!\count($merged)) {
            $merged = new \stdClass();
        }

        return $merged;
    }

    /**
     * Gets the base of the data for the android platforms, with text and localization keys.
     */
    private function getAndroidJsonInner(?string $text): array
    {
        $data = [];
        if (null !== $text) {
            $data['message'] = $text;
        }

        if (null !== $this->localizedKey) {
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
     * @throws MessageTooLongException
     */
    private function getTrimmedJson(callable $inner, int $limit, string $error): array
    {
        $gcmInner = $inner($this->text);
        $gcmInnerJson = json_encode($gcmInner, JSON_UNESCAPED_UNICODE);
        if (($gcmInnerJsonLength = \strlen($gcmInnerJson)) > $limit) {
            $cut = $gcmInnerJsonLength - $limit;
            //Note that strlen returns the byte length of the string
            if ($this->text && ($textLength = \strlen($this->text)) > $cut && $this->allowTrimming) {
                $gcmInner = $inner(mb_strcut($this->text, 0, $textLength - $cut - 3, 'utf8').'...');
            } else {
                throw new MessageTooLongException("$error $gcmInnerJson");
            }
        }

        return $gcmInner;
    }

    /**
     * Merge arrays, deeply.
     */
    private function arrayMergeDeep(array $array1, array $array2): array
    {
        $result = [];
        foreach (\func_get_args() as $array) {
            foreach ($array as $key => $value) {
                // Renumber integer keys as array_merge_recursive() does. Note that PHP
                // automatically converts array keys that are integer strings (e.g., '1')
                // to integers.
                if (\is_int($key)) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && \is_array($result[$key]) && \is_array($value)) {
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
