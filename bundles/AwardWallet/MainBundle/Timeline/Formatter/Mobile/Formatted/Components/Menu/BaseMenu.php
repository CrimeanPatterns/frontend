<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu;

class BaseMenu
{
    /**
     * Do not filter this object.
     *
     * @var int
     */
    public $_keep = 1;

    /**
     * @var Direction
     */
    public $direction;

    /**
     * @var int
     */
    public $accountId;

    /**
     * @var int[]
     */
    public $accountNumbers;

    /**
     * @var Phones|PhoneTab[]
     */
    public $phones;

    /**
     * @var Phones
     */
    public $travelAgencyPhones;

    /**
     * @var string
     */
    public $parkingUrl;

    /**
     * @var bool
     */
    public $allowConfirmChanges = false;

    /**
     * @var string
     */
    public $shareCode;

    /**
     * @var array
     */
    public $itineraryAutologin;
}
