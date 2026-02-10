<?php

namespace AwardWallet\MainBundle\Entity\BookingInvoiceItem;

use AwardWallet\MainBundle\Entity\AbInvoiceItem;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Tax extends AbInvoiceItem
{
    public const TYPE = 1;
}
