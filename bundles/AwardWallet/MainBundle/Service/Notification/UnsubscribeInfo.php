<?php

namespace AwardWallet\MainBundle\Service\Notification;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @NoDI
 */
class UnsubscribeInfo
{
    /**
     * @var Usr
     */
    public $user;
    /**
     * @var MobileDevice
     */
    public $device;

    public function __construct(?Usr $user = null, ?MobileDevice $device = null)
    {
        $this->user = $user;
        $this->device = $device;
    }
}
