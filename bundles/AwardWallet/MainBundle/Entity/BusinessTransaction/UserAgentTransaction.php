<?php

namespace AwardWallet\MainBundle\Entity\BusinessTransaction;

use AwardWallet\MainBundle\Entity\BusinessTransaction;
use AwardWallet\MainBundle\Entity\Useragent;

class UserAgentTransaction extends BusinessTransaction
{
    protected $amount = 30;

    public function __construct(Useragent $ua)
    {
        parent::__construct();
        $this->setSourceID($ua->getUseragentid());
        $this->setSourceDesc($ua->getFullName());
    }
}
