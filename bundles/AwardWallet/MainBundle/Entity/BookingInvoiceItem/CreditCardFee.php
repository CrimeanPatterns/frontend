<?php

namespace AwardWallet\MainBundle\Entity\BookingInvoiceItem;

use AwardWallet\MainBundle\Entity\AbInvoiceItem;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class CreditCardFee extends AbInvoiceItem
{
    public const TYPE = 2;
}
