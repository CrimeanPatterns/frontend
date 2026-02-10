<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\EventDispatcher\Event;

class BusinessPaymentEvent extends Event
{
    /**
     * @var float
     */
    private $amount;

    /**
     * @var Usr
     */
    private $business;

    public function __construct($amount, $business)
    {
        $this->business = $business;
        $this->amount = $amount;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return Usr
     */
    public function getBusiness()
    {
        return $this->business;
    }
}
