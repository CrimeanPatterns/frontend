<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\CartItem;
use Doctrine\ORM\EntityRepository;

/**
 * @template-extends EntityRepository<CartItem>
 */
class CartItemRepository extends EntityRepository
{
}
