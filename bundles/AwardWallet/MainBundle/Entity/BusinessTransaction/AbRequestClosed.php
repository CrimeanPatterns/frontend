<?php

namespace AwardWallet\MainBundle\Entity\BusinessTransaction;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\BusinessTransaction;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AbRequestClosed extends BusinessTransaction
{
    public const TYPE = 5;

    protected $amount = 20;

    public function __construct(AbRequest $abRequest)
    {
        parent::__construct();
        $this->setSourceID($abRequest->getAbRequestID());
        $this->setSourceDesc($abRequest->getAbRequestID());
    }
}
