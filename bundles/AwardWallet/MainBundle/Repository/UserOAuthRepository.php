<?php

namespace AwardWallet\MainBundle\Repository;

use AwardWallet\MainBundle\Entity\UserOAuth;
use AwardWallet\MainBundle\FrameworkExtension\Repository;
use Doctrine\Persistence\ManagerRegistry;

class UserOAuthRepository extends Repository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserOAuth::class);
    }
}
