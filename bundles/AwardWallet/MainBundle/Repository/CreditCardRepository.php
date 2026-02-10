<?php

namespace AwardWallet\MainBundle\Repository;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\FrameworkExtension\Repository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template-extends Repository<CreditCard>
 */
class CreditCardRepository extends Repository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditCard::class);
    }
}
