<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\MainBundle\Entity\Reservation;
use Psr\Log\LoggerInterface;

class SearchValidator
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function canSearchPrices(Reservation $reservation): bool
    {
        $createdDate = $reservation->getReservationDate();

        if ($createdDate === null) {
            $createdDate = $reservation->getFirstSeenDate();
        }

        $daysAfterCreated = $createdDate->diff(new \DateTime())->days;

        if ($daysAfterCreated <= 2) {
            $this->logger->info("hpv canSearchPrices: true, fresh reservation");

            return true;
        }

        if ($daysAfterCreated > 30) {
            $this->logger->info("hpv canSearchPrices: false, more than 30 days after reservation creation");

            return false;
        }

        $checkInDate = new \DateTime($reservation->getCheckindate()->format("Y-m-d H:i:s"), new \DateTimeZone($reservation->getGeotagid()->getTimeZoneLocation()));
        $daysBeforeCheckin = (new \DateTime())->diff($checkInDate)->days;

        if ($daysBeforeCheckin <= 0) {
            $this->logger->info("hpv canSearchPrices: false, days before checkin is too low: $daysBeforeCheckin");

            return false;
        }

        $ratio = $daysAfterCreated / $daysBeforeCheckin;

        $result = $ratio <= 0.15;

        $this->logger->info("hpv canSearchPrices: " . json_encode($result) . ", ratio: $ratio, daysBeforeCheckin: $daysBeforeCheckin, daysAfterCreated: $daysAfterCreated");

        return $result;
    }
}
