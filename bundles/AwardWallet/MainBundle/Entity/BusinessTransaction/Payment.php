<?php

namespace AwardWallet\MainBundle\Entity\BusinessTransaction;

use AwardWallet\MainBundle\Entity\BusinessTransaction;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Payment extends BusinessTransaction
{
    public const TYPE = 6;

    public function __construct($amount)
    {
        parent::__construct();
        $this->setAmount($amount);
    }
}
