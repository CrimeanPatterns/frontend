<?php

namespace AwardWallet\MainBundle\Manager\Ad;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Usr;

class Options
{
    /**
     * @var int ADKIND_* constant
     */
    public $kind;

    /**
     * @var Usr current, for booker ad
     */
    public $user;

    /**
     * @var string for ADKIND_EMAIL kind
     */
    public $emailType;

    /**
     * @var Account[] to retrieve data ProviderID, provider Kind and ChangeCount
     */
    public $accounts = [];

    /**
     * @var Provider[] to retrieve data ProviderID, provider Kind
     */
    public $providers = [];

    /**
     * @var array
     *      [
     *          [ProviderID => ["Kind" => 1, "ChangeCount" => 3]],
     *          [ProviderID => ["Kind" => 2, "ChangeCount" => 1]],
     *          ...
     *      ]
     */
    public $flatData = [];

    /**
     * @var string
     */
    public $clientIp;

    public $filter;

    /**
     * Options constructor.
     *
     * @param int $advtKind
     * @param string|null $emailType
     */
    public function __construct($advtKind, ?Usr $user = null, $emailType = null)
    {
        $this->kind = $advtKind;
        $this->user = $user;
        $this->emailType = $emailType;
    }
}
