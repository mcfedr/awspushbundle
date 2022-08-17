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
    public const GCM_NO_COLLAPSE = 'do_not_collapse';
    public const NO_COLLAPSE = 'do_not_collapse';

    public const ADM_MAX_LENGTH = 6144;
    /**
     * This has changed over time.
     *
     * For iOS 9+ 4KiB (Only if using HTTP/2 API)
     * For iOS 8 2KiB
     * For iOS 6- 256B
     */
    public const APNS_MAX_LENGTH = 4096;
    public const APNS_VOIP_MAX_LENGTH = 5120;
    /**
     * This is a bit hard to be exact about,
     * But seems to be the limit for data and notification fields combined.
     */
    public const FCM_MAX_LENGTH = 4096;
    public const GCM_MAX_LENGTH = 4096;

    public const DEFAULT_PLATFORMS = [self::PLATFORM_ADM, self::PLATFORM_APNS, self::PLATFORM_APNS_VOIP, self::PLATFORM_GCM];
    public const DEFAULT_PLATFORMS_NEXT = [self::PLATFORM_APNS, self::PLATFORM_FCM];
    public const ALL_PLATFORMS = [self::PLATFORM_ADM, self::PLATFORM_APNS, self::PLATFORM_APNS_VOIP, self::PLATFORM_FCM, self::PLATFORM_GCM];

    public const PLATFORM_ADM = 'adm';
    public const PLATFORM_APNS = 'apns';
    public const PLATFORM_APNS_VOIP = 'apns_voip';
    public const PLATFORM_FCM = 'fcm';
    /**
     * @deprecated use PLATFORM_FCM
     * @see PLATFORM_FCM
     */
    public const PLATFORM_GCM = 'gcm';

    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_NORMAL = 'normal';

    public const PUSH_TYPE_ALERT = 'alert';
    public const PUSH_TYPE_BACKGROUND = 'background';

    /**
     * The content of the alert message.
     * The text is automatically trimmed when sending to APNS, GCM and FCM.
     * For ADM and GCM it will be in data.message.
     * For APNS it will be in aps.alert.body.
     * For FCM it will be in notification.body.
     *
     * @var ?string
     */
    private $text;

    /**
     * The key of a localized string that will form the message displayed.
     * If this is specified text will not be sent to APNS.
     * For ADM and GCM it will be in data.message-loc-key.
     * For APNS it will be in aps.alert.loc-key.
     * For FCM it will be in or notification.body_loc_key.
     *
     * @var ?string
     */
    private $localizedKey;

    /**
     * Arguments for the localized message
     * If you are using iOS these should be strings only, plural localization doesn't work!
     * For ADM and GCM it will be in data.message-loc-args.
     * For APNS it will be in aps.alert.loc-args.
     * For FCM it will be in notification.body_loc_args.
     *
     * @var ?array
     */
    private $localizedArguments;

    /**
     * The title of the notification.
     * For ADM and GCM it will be in data.title.
     * For APNS it will be in aps.alert.title.
     * For FCM it will be in in notification.title.
     *
     * @var ?string
     */
    private $title;

    /**
     * The key of a localized title string that will form the title displayed.
     * If this is specified title will not be sent to APNS.
     * For ADM and GCM it will be in data.title-loc-key.
     * For APNS it will be in aps.alert.title-loc-key.
     * For FCM it will be in notification.title_loc_key.
     *
     * @var ?string
     */
    private $titleLocalizedKey;

    /**
     * Arguments for the localized title.
     * If you are using iOS these should be strings only, plural localization doesn't work!
     * For ADM and GCM it will be in data.title-loc-args.
     * For APNS it will be in aps.alert.title-loc-args.
     * For FCM it will be in notification.title_loc_args.
     *
     * @var ?array
     */
    private $titleLocalizedArguments;

    /**
     * Additional information that explains the purpose of the notification.
     * For ADM and GCM it will be in data.subtitle.
     * For APNS it will be in aps.alert.subtitle.
     * For FCM it will be ignored.
     *
     * @var ?string
     */
    private $subtitle;

    /**
     * The key of a localized subtitle string that will form the title displayed.
     * If this is specified subtitle will not be sent to APNS.
     * For ADM and GCM it will be in data.subtitle-loc-key.
     * For APNS it will be in aps.alert.subtitle-loc-key.
     * For FCM it will be ignored.
     *
     * @var ?string
     */
    private $subtitleLocalizedKey;

    /**
     * Arguments for the localized subtitle.
     * If you are using iOS these should be strings only, plural localization doesn't work!
     * For ADM and GCM it will be in data.subtitle-loc-args.
     * For APNS it will be in aps.alert.subtitle-loc-args.
     * For FCM it will be ignored.
     *
     * @var ?array
     */
    private $subtitleLocalizedArguments;

    /**
     * The notification’s type.
     * For ADM and GCM it will be ignored.
     * For APNS it will be in aps.category.
     * For FCM it will be in notification.android_channel_id.
     *
     * @var ?string
     */
    private $category;

    /**
     * This is notification priority for GCM should be 'high' or 'normal'.
     * High priority is default.
     * GCM and FCM only.
     *
     * @var string
     */
    private $priority = self::PRIORITY_HIGH;

    /**
     * The number to display in a badge on your app’s icon.
     * APNS only. It will be in aps.badge.
     *
     * @var ?int
     */
    private $badge;

    /**
     * The name of a sound file in your app’s main bundle or in the
     * Library/Sounds folder of your app’s container directory.
     * For ADM and GCM it will be data.sound.
     * For APNS. It will be in aps.sound.
     * For FCM it will be in notification.sound.
     *
     * @var ?string
     */
    private $sound;

    /**
     * Use these keys to configure the sound for a critical alert.
     * APNS only. It will be in aps.sound.
     * https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/generating_a_remote_notification#2990112.
     *
     * @var ?array
     */
    private $apnsSound;

    /**
     * The background notification flag.
     * Including this key means that when your app is launched in the background or resumed.
     * APNS only. It will be in aps.content-available.
     *
     * @var ?bool
     */
    private $contentAvailable;

    /**
     * An app-specific identifier for grouping related notifications.
     * APNS only. Tt will be in aps.thread-id.
     *
     * @var ?string
     */
    private $threadId;

    /**
     * The notification service app extension flag.
     * APNS only. It will be in aps.mutable-content.
     *
     * @var ?bool
     */
    private $mutableContent;

    /**
     * This is the data to send to all services, it will be deep merged with the other data.
     *
     * @var array
     */
    private $custom = [];

    /**
     * If set, will be sent to ADM, deep merged with ['message' => $text] in the data field.
     *
     * @var ?array
     */
    private $admData;

    /**
     * If set, will be sent to APNS, deep merged as the top level with ['aps' => ...].
     *
     * @var ?array
     */
    private $apnsData;

    /**
     * If set, will be sent to FCM, deep merged with data field.
     *
     * @var ?array
     */
    private $fcmData;

    /**
     * If set, will be sent to FCM, deep merged at the top level with ['data' => ..., 'notification' => ...].
     *
     * @var ?array
     */
    private $fcmTopLevelData;

    /**
     * If set, will be sent to GCM, deep merged with ['message' => $text] in the data field.
     *
     * @var ?array
     */
    private $gcmData;

    /**
     * The collapseKey will be sent for GCM, FCM and ADM
     * and if set, apns-collapse-id is sent for APNS.
     *
     * @var string
     */
    private $collapseKey = self::NO_COLLAPSE;

    /**
     * GCM, FCM and ADM only.
     *
     * GCM: default is 4 weeks
     * ADM: default is 1 week
     *
     * @var ?int number of seconds that the server should retain the message
     */
    private $ttl;

    /**
     * GCM and FCM only.
     *
     * @var bool
     */
    private $delayWhileIdle = false;

    /**
     * If set then the text content of the message will be trimmed where\
     * necessary to fit in the length limits of each platform.
     *
     * @var bool
     *
     * @see Message::APNS_MAX_LENGTH
     * @see Message::FCM_MAX_LENGTH
     * @see Message::GCM_MAX_LENGTH

     * @see Message::ADM_MAX_LENGTH
     */
    private $allowTrimming = true;

    /**
     * Platforms that this message will create JSON for, and throw errors for.
     *
     * @var array
     *
     * @see Message::PLATFORM_ADM
     * @see Message::PLATFORM_APNS
     * @see Message::PLATFORM_APNS_VOIP
     * @see Message::PLATFORM_FCM
     * @see Message::PLATFORM_GCM
     */
    private $platforms = self::DEFAULT_PLATFORMS;

    /**
     * APNS only. It will be in apns-push-type.
     * One of those "alert", "background", "voip", "complication", "fileprovider", "mdm".
     *
     * @see https://developer.apple.com/documentation/usernotifications/setting_up_a_remote_notification_server/sending_notification_requests_to_apns
     *
     * @var ?string
     */
    private $pushType = self::PUSH_TYPE_ALERT;

    /**
     * Marks that the platforms field has been set.
     *
     * @var bool
     */
    private $platformsCustomized = false;

    public function __construct(?string $text = null)
    {
        $this->text = $text;
    }

    public function getBadge(): ?int
    {
        return $this->badge;
    }

    public function setBadge(?int $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    public function isContentAvailable(): ?bool
    {
        return $this->contentAvailable;
    }

    public function setContentAvailable(?bool $contentAvailable): self
    {
        $this->contentAvailable = $contentAvailable;

        $contentAvailable && $this->setPushType(self::PUSH_TYPE_BACKGROUND);

        return $this;
    }

    public function getCustom(): array
    {
        return $this->custom;
    }

    public function setCustom(array $custom): self
    {
        $this->custom = $custom;

        return $this;
    }

    public function getSound(): ?string
    {
        return $this->sound;
    }

    public function setSound(?string $sound): self
    {
        $this->sound = $sound;

        return $this;
    }

    public function getApnsSound(): ?array
    {
        return $this->apnsSound;
    }

    public function setApnsSound(?array $apnsSound): self
    {
        $this->apnsSound = $apnsSound;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getTitleLocalizedKey(): ?string
    {
        return $this->titleLocalizedKey;
    }

    public function setTitleLocalizedKey(?string $titleLocalizedKey): self
    {
        $this->titleLocalizedKey = $titleLocalizedKey;

        return $this;
    }

    public function getTitleLocalizedArguments(): ?array
    {
        return $this->titleLocalizedArguments;
    }

    public function setTitleLocalizedArguments(?array $titleLocalizedArguments): self
    {
        $this->titleLocalizedArguments = $titleLocalizedArguments;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getSubtitleLocalizedKey(): ?string
    {
        return $this->subtitleLocalizedKey;
    }

    public function setSubtitleLocalizedKey(?string $subtitleLocalizedKey): self
    {
        $this->subtitleLocalizedKey = $subtitleLocalizedKey;

        return $this;
    }

    public function getSubtitleLocalizedArguments(): ?string
    {
        return $this->subtitleLocalizedArguments;
    }

    public function setSubtitleLocalizedArguments(?array $subtitleLocalizedArguments): self
    {
        $this->subtitleLocalizedArguments = $subtitleLocalizedArguments;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getLocalizedKey(): ?string
    {
        return $this->localizedKey;
    }

    public function setLocalizedKey(?string $localizedKey): self
    {
        $this->localizedKey = $localizedKey;

        return $this;
    }

    public function getLocalizedArguments(): ?array
    {
        return $this->localizedArguments;
    }

    public function setLocalizedArguments(?array $localizedArguments = null): self
    {
        $this->localizedArguments = $localizedArguments;

        return $this;
    }

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

    public function setAllowTrimming(bool $allowTrimming): self
    {
        $this->allowTrimming = $allowTrimming;

        return $this;
    }

    public function getCollapseKey(): string
    {
        return $this->collapseKey;
    }

    public function setCollapseKey(?string $collapseKey): self
    {
        $this->collapseKey = $collapseKey ?: static::NO_COLLAPSE;

        return $this;
    }

    public function getDelayWhileIdle(): bool
    {
        return $this->delayWhileIdle;
    }

    public function setDelayWhileIdle(bool $delayWhileIdle): self
    {
        $this->delayWhileIdle = $delayWhileIdle;

        return $this;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function setTtl(?int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    public function getGcmData(): ?array
    {
        return $this->gcmData;
    }

    public function setGcmData(?array $gcmData): self
    {
        $this->gcmData = $gcmData;

        return $this;
    }

    public function getFcmData(): ?array
    {
        return $this->fcmData;
    }

    public function setFcmData(?array $fcmData): self
    {
        $this->fcmData = $fcmData;

        return $this;
    }

    public function getFcmTopLevelData(): ?array
    {
        return $this->fcmTopLevelData;
    }

    public function setFcmTopLevelData(?array $fcmTopLevelData): self
    {
        $this->fcmTopLevelData = $fcmTopLevelData;

        return $this;
    }

    public function getAdmData(): ?array
    {
        return $this->admData;
    }

    public function setAdmData(?array $admData): self
    {
        $this->admData = $admData;

        return $this;
    }

    public function getApnsData(): ?array
    {
        return $this->apnsData;
    }

    public function setApnsData(?array $apnsData): self
    {
        $this->apnsData = $apnsData;

        return $this;
    }

    public function getPlatforms(): ?array
    {
        return $this->platforms;
    }

    public function setPlatforms(array $platforms): self
    {
        $this->platforms = $platforms;
        $this->platformsCustomized = true;

        return $this;
    }

    public function isPlatformsCustomized(): bool
    {
        return $this->platformsCustomized;
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

    public function isMutableContent(): ?bool
    {
        return $this->mutableContent;
    }

    public function setMutableContent(?bool $mutableContent): self
    {
        $this->mutableContent = $mutableContent;

        return $this;
    }

    public function getPushType(): string
    {
        return $this->pushType;
    }

    public function setPushType($pushType): self
    {
        $this->pushType = $pushType;

        return $this;
    }

    /**
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $data = [
            'default' => $this->text,
        ];

        if (\in_array(self::PLATFORM_ADM, $this->platforms)) {
            $data['ADM'] = $this->getAdmJson();
        }

        if (\in_array(self::PLATFORM_APNS, $this->platforms)) {
            $data['APNS'] = $data['APNS_SANDBOX'] = $this->getApnsJson(self::APNS_MAX_LENGTH, 'You message for APNS is too long');
        }

        if (\in_array(self::PLATFORM_APNS_VOIP, $this->platforms)) {
            $data['APNS_VOIP'] = $data['APNS_VOIP_SANDBOX'] = $this->getApnsJson(self::APNS_VOIP_MAX_LENGTH, 'You message for APNS VOIP is too long');
        }

        if (\in_array(self::PLATFORM_FCM, $this->platforms)) {
            $data['FCM'] = $this->getFcmJson();
        } elseif (\in_array(self::PLATFORM_GCM, $this->platforms)) {
            $data['GCM'] = $this->getGcmJson();
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
    private function getApnsJson(int $length, string $message): string
    {
        return json_encode($this->getTrimmedData([$this, 'getApnsJsonInner'], $length, $message), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Get the correct apple push notification server data.
     *
     * @return mixed
     */
    private function getApnsJsonInner(?string $text)
    {
        $apns = [
            'aps' => [],
        ];

        if (null !== $this->localizedKey) {
            $apns['aps']['alert'] = $apns['aps']['alert'] ?? [];
            $apns['aps']['alert']['loc-key'] = $this->localizedKey;
            if ($this->localizedArguments) {
                $apns['aps']['alert']['loc-args'] = $this->localizedArguments;
            }
        } elseif (null !== $text) {
            $apns['aps']['alert'] = $apns['aps']['alert'] ?? [];
            $apns['aps']['alert']['body'] = $text;
        }

        if (null !== $this->titleLocalizedKey) {
            $apns['aps']['alert'] = $apns['aps']['alert'] ?? [];
            $apns['aps']['alert']['title-loc-key'] = $this->titleLocalizedKey;
            if (null !== $this->titleLocalizedArguments) {
                $apns['aps']['alert']['title-loc-args'] = $this->titleLocalizedArguments;
            }
        } elseif (null !== $this->title) {
            $apns['aps']['alert'] = $apns['aps']['alert'] ?? [];
            $apns['aps']['alert']['title'] = $this->title;
        }

        if (null !== $this->subtitleLocalizedKey) {
            $apns['aps']['alert'] = $apns['aps']['alert'] ?? [];
            $apns['aps']['alert']['subtitle-loc-key'] = $this->subtitleLocalizedKey;
            if (null !== $this->subtitleLocalizedArguments) {
                $apns['aps']['alert']['subtitle-loc-args'] = $this->subtitleLocalizedArguments;
            }
        } elseif (null !== $this->subtitle) {
            $apns['aps']['alert'] = $apns['aps']['alert'] ?? [];
            $apns['aps']['alert']['subtitle'] = $this->subtitle;
        }

        if (null !== $this->category) {
            $apns['aps']['category'] = $this->category;
        }

        if ($this->contentAvailable) {
            $apns['aps']['content-available'] = 1;
        }

        if (null !== $this->badge) {
            $apns['aps']['badge'] = $this->badge;
        }

        if (null !== $this->sound) {
            $apns['aps']['sound'] = $this->sound;
        }

        if (null !== $this->apnsSound) {
            $apns['aps']['sound'] = $this->apnsSound;
        }

        if (null !== $this->threadId) {
            $apns['aps']['thread-id'] = $this->threadId;
        }

        if (null !== $this->mutableContent) {
            $apns['aps']['mutable-content'] = 1;
        }

        $merged = $this->arrayMergeDeep($apns, $this->custom, $this->apnsData ?: []);

        // Force aps to be an object, because it shouldnt get encoded as [] but as {}
        if (!\count($merged['aps'])) {
            $merged['aps'] = new \stdClass();
        }

        return $merged;
    }

    /**
     * Get the json to send via Firebase Cloud Messaging
     * For FCM the max length is for the data field only.
     *
     * @throws MessageTooLongException
     */
    private function getFcmJson(): string
    {
        return json_encode(array_merge([
            'collapse_key' => $this->collapseKey,
            'time_to_live' => $this->ttl,
            'delay_while_idle' => $this->delayWhileIdle,
            'priority' => $this->priority,
        ], $this->getTrimmedData([$this, 'getFcmJsonInner'], static::FCM_MAX_LENGTH, 'You message for FCM is too long')), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Gets the data part of the FCM message.
     *
     * @return mixed
     */
    private function getFcmJsonInner(?string $text)
    {
        $fcm = [];

        $data = $this->arrayMergeDeep([], $this->custom, $this->fcmData ?: []);
        if (\count($data)) {
            $fcm['data'] = $data;
        }

        $notification = [];
        if (null !== $text) {
            $notification['body'] = $text;
        }

        if (null !== $this->localizedKey) {
            $notification['body_loc_key'] = $this->localizedKey;
            if ($this->localizedArguments) {
                $notification['body_loc_args'] = $this->localizedArguments;
            }
        }

        if (null !== $this->title) {
            $notification['title'] = $this->title;
        }

        if (null !== $this->titleLocalizedKey) {
            $notification['title_loc_key'] = $this->titleLocalizedKey;
            if ($this->titleLocalizedArguments) {
                $notification['title_loc_args'] = $this->titleLocalizedArguments;
            }
        }

        if (null !== $this->sound) {
            $notification['sound'] = $this->sound;
        }

        if (null !== $this->category) {
            $notification['android_channel_id'] = $this->category;
        }

        if (\count($notification)) {
            $fcm['notification'] = $notification;
        }

        $fcm = $this->arrayMergeDeep($fcm, $this->fcmTopLevelData ?: []);

        // Force to be an object, because it shouldn't get encoded as [] but as {}
        if (!isset($fcm['data']) && !isset($fcm['notification'])) {
            $fcm['data'] = new \stdClass();
        }

        return $fcm;
    }

    /**
     * Get the json to send via Amazon Device Messaging.
     */
    private function getAdmJson(): string
    {
        $adm = [
            'data' => $this->getTrimmedData([$this, 'getAdmJsonInner'], static::ADM_MAX_LENGTH, 'You message for ADM is too long'),
            'expiresAfter' => $this->ttl,
        ];

        foreach ($adm['data'] as $key => $value) {
            if (!\is_string($value)) {
                $adm['data']["{$key}_json"] = json_encode($value, JSON_THROW_ON_ERROR);
                unset($adm['data'][$key]);
            }
        }

        if ($this->collapseKey != static::NO_COLLAPSE) {
            $adm['consolidationKey'] = $this->collapseKey;
        }

        return json_encode($adm, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Gets the data part of the GCM message.
     *
     * @return mixed
     */
    private function getAdmJsonInner(?string $text)
    {
        $data = $this->getAndroidJsonInner($text);

        $merged = $this->arrayMergeDeep($data, $this->custom, $this->admData ?: []);

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
            'data' => $this->getTrimmedData([$this, 'getGcmJsonInner'], static::GCM_MAX_LENGTH, 'You message for GCM is too long'),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Gets the data part of the GCM message.
     *
     * @return mixed
     */
    private function getGcmJsonInner(?string $text)
    {
        $data = $this->getAndroidJsonInner($text);

        $merged = $this->arrayMergeDeep($data, $this->custom, $this->gcmData ?: []);

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

        if (null !== $this->title) {
            $data['title'] = $this->title;
        }

        if (null !== $this->titleLocalizedKey) {
            $data['title-loc-key'] = $this->titleLocalizedKey;
            if ($this->titleLocalizedArguments) {
                $data['title-loc-args'] = $this->titleLocalizedArguments;
            }
        }

        if (null !== $this->subtitle) {
            $data['subtitle'] = $this->subtitle;
        }

        if (null !== $this->subtitleLocalizedKey) {
            $data['subtitle-loc-key'] = $this->subtitleLocalizedKey;
            if ($this->subtitleLocalizedArguments) {
                $data['subtitle-loc-args'] = $this->subtitleLocalizedArguments;
            }
        }

        if (null !== $this->sound) {
            $data['sound'] = $this->sound;
        }

        return $data;
    }

    /**
     * Using a inner function gets the data, and trys again if its too long by trimming the text.
     *
     * @throws MessageTooLongException
     *
     * @return mixed
     */
    private function getTrimmedData(callable $inner, int $limit, string $error)
    {
        $innerData = $inner($this->text);
        $innerJson = json_encode($innerData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (($innerJsonLength = \strlen($innerJson)) > $limit) {
            // Note that strlen returns the byte length of the string
            if ($this->allowTrimming && $this->text && ($textLength = \strlen($this->text)) > ($cut = $innerJsonLength - $limit)) {
                $innerData = $inner(mb_strcut($this->text, 0, $textLength - $cut - 3, 'utf8').'...');
            } else {
                throw new MessageTooLongException("$error $innerJson");
            }
        }

        return $innerData;
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
