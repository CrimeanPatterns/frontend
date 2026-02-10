<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Request;

interface ScheduleRequestInterface extends RequestInterface
{
    /**
     * next update datetime, or false on dont update, or true on now.
     *
     * @param \DateTime $updateDate
     * @param \DateTime $depDate
     * @param \DateTime $arrDate
     * @return bool|\DateTime
     */
    public function getNextUpdate($updateDate, $depDate, $arrDate);

    /**
     * seconds to next update or false.
     *
     * @param \DateTime $updateDate
     * @param \DateTime $depDate
     * @param \DateTime $arrDate
     * @return bool|int
     */
    public function getNextUpdateInSeconds($updateDate, $depDate, $arrDate);
}
