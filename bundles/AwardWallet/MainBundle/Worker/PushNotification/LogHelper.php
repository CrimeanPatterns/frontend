<?php

namespace AwardWallet\MainBundle\Worker\PushNotification;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Service\Notification\Content;
use AwardWallet\MainBundle\Worker\PushNotification\DTO\Notification;

class LogHelper
{
    /**
     * @var string
     */
    private $workerName;
    /**
     * @var array
     */
    private $mixin;

    public function __construct($workerName, array $mixin = [])
    {
        $this->workerName = $workerName;
        $this->mixin = $mixin;
    }

    /**
     * @return array
     */
    public function getDefaultContext()
    {
        $result = [
            '_aw_server_module' => 'push',
            '_aw_push_worker' => $this->workerName,
        ];

        if ($this->mixin) {
            $result = array_merge($result, $this->mixin);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getDefaultFailContext()
    {
        return array_merge($this->getDefaultContext(), ['_aw_push_fail' => 1]);
    }

    /**
     * @return array
     */
    public function getDefaultSuccessContext()
    {
        return array_merge($this->getDefaultSuccessContext(), ['_aw_push_success' => 1]);
    }

    /**
     * @return array
     */
    public function getContext(Notification $notification, array $sup = [])
    {
        $default = $this->getDefaultContext();
        $context = [
            '_aw_push_platform' => MobileDevice::getTypeName($notification->getDeviceType()),
            '_aw_push_device_id' => $notification->getDeviceId(),
            '_aw_push_device_key' => $notification->getDeviceKey(),
            '_aw_push_device_version' => $notification->getDeviceAppVersion(),
            '_aw_push_type' => $notification->getType(),
            '_aw_userid' => $notification->getUserId(),
            'UserID' => $notification->getUserId(),
        ];

        if ($retries = $notification->getRetries()) {
            $context['_aw_push_retries_exp'] = $retries;
        }

        if (!\is_null($createdAt = $notification->getCreatedAt())) {
            $context['created_at_timestamp'] = $createdAt;
        }

        if (null !== ($deadlineTimestamp = $notification->getOptions()->getDeadlineTimestamp())) {
            $context['_aw_push_deadline_timestamp'] = $deadlineTimestamp;
        }

        if (null !== ($priority = $notification->getOptions()->getPriority())) {
            $context['_aw_push_priority'] = $priority;
        }

        return array_merge($default, $context, $sup, $notification->getOptions()->getLogContext());
    }

    public function getContextByContent(Content $content): array
    {
        $default = $this->getDefaultContext();
        $options = $content->options;

        $context = [
            '_aw_push_type' => Content::getTypeName($content->type),
        ];

        if ($options) {
            if (!\is_null($deadlineTimestamp = $options->getDeadlineTimestamp())) {
                $context['_aw_push_deadline_timestamp'] = $deadlineTimestamp;
            }

            if (!\is_null($priority = $options->getPriority())) {
                $context['_aw_push_priority'] = $priority;
            }
        }

        return \array_merge($default, $context);
    }

    /**
     * @return array
     */
    public function getFailContext(Notification $notification, array $sup = [])
    {
        return array_merge($this->getContext($notification, $sup), ['_aw_push_fail' => 1]);
    }

    /**
     * @return array
     */
    public function getSuccessContext(Notification $notification, array $sup = [])
    {
        return array_merge($this->getContext($notification, $sup), ['_aw_push_success' => 1]);
    }
}
