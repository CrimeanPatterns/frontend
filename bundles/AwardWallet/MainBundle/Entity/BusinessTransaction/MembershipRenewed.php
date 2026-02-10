<?php

namespace AwardWallet\MainBundle\Entity\BusinessTransaction;

use AwardWallet\MainBundle\Entity\BusinessTransaction;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class MembershipRenewed extends BusinessTransaction
{
    public const TYPE = 2;
    public const AMOUNT = 4;

    public function __construct($count)
    {
        parent::__construct();
        $this->amount = $count * self::AMOUNT;
    }

    public function isFreeWhenTrial()
    {
        return true;
    }

    public function getSourceDesc()
    {
        return new \DateTime('@' . parent::getSourceDesc());
    }

    /**
     * @param \DateTime $sourceDesc
     * @return $this
     */
    public function setSourceDesc($sourceDesc)
    {
        return parent::setSourceDesc($sourceDesc->getTimestamp());
    }
}
