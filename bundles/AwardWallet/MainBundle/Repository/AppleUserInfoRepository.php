<?php

namespace AwardWallet\MainBundle\Repository;

use AwardWallet\MainBundle\Entity\AppleUserInfo;
use AwardWallet\MainBundle\FrameworkExtension\Repository;
use Doctrine\Persistence\ManagerRegistry;

class AppleUserInfoRepository extends Repository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppleUserInfo::class);
    }
}
