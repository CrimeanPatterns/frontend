<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Response;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class CommonResponse implements ResponseInterface
{
    /** @var \DateTime */
    protected $createDate;

    public function __construct()
    {
        $this->createDate = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * @return self
     */
    public function setCreateDate(\DateTime $date)
    {
        $this->createDate = $date;

        return $this;
    }
}
