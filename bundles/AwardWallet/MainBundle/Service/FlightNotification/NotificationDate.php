<?php

namespace AwardWallet\MainBundle\Service\FlightNotification;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Tripsegment;

/**
 * @NoDI()
 */
class NotificationDate
{
    /**
     * @param string $kind kind of notifications, OffsetHandler::KIND_XXX
     */
    public static function getDate(Tripsegment $tripSegment, string $kind): ?\DateTimeInterface
    {
        switch ($kind) {
            case OffsetHandler::KIND_PRECHECKIN:
                return $tripSegment->getPreCheckinNotificationDate();

            case OffsetHandler::KIND_CHECKIN:
                return $tripSegment->getCheckinnotificationdate();

            case OffsetHandler::KIND_DEPARTURE:
                return $tripSegment->getFlightDepartureNotificationDate();

            case OffsetHandler::KIND_BOARDING:
                return $tripSegment->getFlightBoardingNotificationDate();
        }

        throw new \InvalidArgumentException(sprintf('Unknown kind "%s"', $kind));
    }

    /**
     * @param string $kind kind of notifications, OffsetHandler::KIND_XXX
     */
    public static function setDate(Tripsegment $tripSegment, string $kind, ?\DateTime $dateTime): void
    {
        switch ($kind) {
            case OffsetHandler::KIND_PRECHECKIN:
                $tripSegment->setPreCheckinNotificationDate($dateTime);

                break;

            case OffsetHandler::KIND_CHECKIN:
                $tripSegment->setCheckinnotificationdate($dateTime);

                break;

            case OffsetHandler::KIND_DEPARTURE:
                $tripSegment->setFlightDepartureNotificationDate($dateTime);

                break;

            case OffsetHandler::KIND_BOARDING:
                $tripSegment->setFlightBoardingNotificationDate($dateTime);

                break;
        }
    }

    /**
     * @param string $kind kind of notifications, OffsetHandler::KIND_XXX
     */
    public static function getField(string $kind): string
    {
        switch ($kind) {
            case OffsetHandler::KIND_PRECHECKIN:
                return 'PreCheckinNotificationDate';

            case OffsetHandler::KIND_CHECKIN:
                return 'CheckinNotificationDate';

            case OffsetHandler::KIND_DEPARTURE:
                return 'FlightDepartureNotificationDate';

            case OffsetHandler::KIND_BOARDING:
                return 'FlightBoardingNotificationDate';
        }

        throw new \InvalidArgumentException(sprintf('Unknown kind "%s"', $kind));
    }
}
