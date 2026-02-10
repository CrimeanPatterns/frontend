<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Engine;

interface LockerInterface
{
    /**
     * @return bool
     */
    public function failure();

    /**
     * @return bool
     */
    public function locked();

    /**
     * @return void
     */
    public function reset();
}
