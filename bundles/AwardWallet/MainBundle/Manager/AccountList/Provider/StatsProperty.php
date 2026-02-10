<?php

namespace AwardWallet\MainBundle\Manager\AccountList\Provider;

use AwardWallet\MainBundle\Entity\Provider;

class StatsProperty
{
    /**
     * @var string
     */
    public $keywords;

    /**
     * @var int
     */
    public $popularity;

    /**
     * @var bool
     */
    public $down;

    /**
     * @var bool
     */
    public $corporate;

    public function __construct(Provider $provider)
    {
        $this->keywords = $provider->getKeywords();
        $this->popularity = $provider->getAccounts();
        $this->down = in_array($provider->getProviderid(), [7, 16, 26]);
        $this->corporate = $provider->getCorporate();
    }
}
