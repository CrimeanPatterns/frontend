<?php

namespace AwardWallet\MainBundle\Entity\BookingInvoiceItem;

use AwardWallet\MainBundle\Entity\AbInvoiceItem;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Item extends AbInvoiceItem
{
    public const TYPE = 100;
}
