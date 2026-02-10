<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

use AwardWallet\MainBundle\Service\FlightInfo\Engine\CacherInterface;

interface CachedRequestInterface extends RequestInterface
{
    /**
     * @return self
     */
    public function setCacher(CacherInterface $cacher);
}
