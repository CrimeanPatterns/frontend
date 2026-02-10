<?php

namespace AwardWallet\MainBundle\Service\ExpirationDate\Template;

use AwardWallet\MainBundle\Service\ExpirationDate\Expire;

class AccountExpireEvent
{
    /**
     * @var Expire
     */
    public $account = [];

    /**
     * @var int[]
     */
    public $daysBeforeAlarm = [];
}
