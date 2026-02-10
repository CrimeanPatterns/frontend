<?php

namespace AwardWallet\MainBundle\Service\ExpirationDate\Template;

use AwardWallet\MainBundle\Entity\Providercoupon;

class PassportExpireEvent
{
    /**
     * @var Providercoupon
     */
    public $passport;

    /**
     * @var int
     */
    public $expiresInMonths = 6;

    /**
     * @var int[]
     */
    public $weeksBeforeAlarm = [];
}
