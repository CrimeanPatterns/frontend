<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Response;

interface ResponseInterface
{
    /**
     * @return \DateTime
     */
    public function getCreateDate();

    /**
     * @return self
     */
    public function setCreateDate(\DateTime $date);
}
