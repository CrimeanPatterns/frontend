<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\DTO;

class DeviceAction
{
    use Versioning;

    public const VERSION = 1;

    public const RENEW = 1;
    public const REMOVE = 2;
    public const QUIET = 3;
    public const ERROR = 4;

    private static $actionNames = [
        self::RENEW => 'renew',
        self::REMOVE => 'remove',
        self::QUIET => 'quiet',
        self::ERROR => 'error',
    ];

    /**
     * @var int
     */
    private $action;

    /**
     * @var Notification
     */
    private $notification;

    private $data;

    public function __construct(Notification $notification, $action, $data = null)
    {
        $this->setVersion(self::VERSION);
        $this->action = $action;
        $this->notification = $notification;
        $this->data = $data;
    }

    public static function getActionName($type)
    {
        return self::$actionNames[$type];
    }

    /**
     * @return int
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return Notification
     */
    public function getNotification()
    {
        return $this->notification;
    }

    public function getData()
    {
        return $this->data;
    }

    protected function getClassVersion()
    {
        return self::VERSION;
    }
}
