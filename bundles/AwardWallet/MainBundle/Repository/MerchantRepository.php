<?php

namespace AwardWallet\MainBundle\Repository;

use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\FrameworkExtension\Repository;
use Doctrine\Persistence\ManagerRegistry;

class MerchantRepository extends Repository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Merchant::class);
    }
}
