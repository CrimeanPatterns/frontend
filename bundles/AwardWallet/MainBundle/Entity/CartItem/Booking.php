<?php

namespace AwardWallet\MainBundle\Entity\CartItem;

use AwardWallet\MainBundle\Entity\CartItem;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Booking extends CartItem
{
    public const TYPE = 9;

    /**
     * @return int
     */
    public function getRequestId()
    {
        return $this->getId();
    }

    /**
     * @param int $requestId
     */
    public function setRequestId($requestId)
    {
        $this->setId($requestId);

        return $this;
    }

    public function getInvoiceId()
    {
        return $this->getUserdata();
    }

    public function setInvoiceId($invoiceId)
    {
        $this->setUserdata($invoiceId);

        return $this;
    }
}
