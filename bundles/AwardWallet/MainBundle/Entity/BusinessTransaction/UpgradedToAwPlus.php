<?php

namespace AwardWallet\MainBundle\Entity\BusinessTransaction;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UpgradedToAwPlus extends UserAgentTransaction
{
    public const TYPE = 4;
}
