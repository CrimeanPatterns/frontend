<?php

namespace AwardWallet\MainBundle\Entity\Listener;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Entity\Trip;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;

class ItineraryListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function prePersist(Itinerary $itinerary)
    {
        $this->fixDates($itinerary);
    }

    public function preUpdate(Itinerary $itinerary)
    {
        $this->fixDates($itinerary);
    }

    public function postPersist(Itinerary $itinerary, LifecycleEventArgs $args)
    {
        $this->checkItinerary($itinerary, 'postPersist');
    }

    public function postUpdate(Itinerary $itinerary, LifecycleEventArgs $args)
    {
        $this->checkItinerary($itinerary, 'postUpdate', $args);
    }

    private function fixDates(Itinerary $itinerary)
    {
        if ($itinerary instanceof Restaurant) {
            $startDate = Geotag::getLocalDateTimeByGeoTag($itinerary->getStartDate(), $itinerary->getGeotagid());
            $endDate = Geotag::getLocalDateTimeByGeoTag($itinerary->getEndDate(), $itinerary->getGeotagid());

            if (isset($startDate, $endDate) && $startDate > $endDate) {
                $itinerary->setEndDate(null);
            }
        } elseif ($itinerary instanceof Reservation) {
            $startDate = Geotag::getLocalDateTimeByGeoTag($itinerary->getCheckindate(), $itinerary->getGeotagid());
            $endDate = Geotag::getLocalDateTimeByGeoTag($itinerary->getCheckoutdate(), $itinerary->getGeotagid());

            if (isset($startDate, $endDate) && $startDate > $endDate) {
                if (
                    $startDate->format('Y-m-d') === $endDate->format('Y-m-d')
                    && $startDate->format('H:i') === '16:00'
                    && $endDate->format('H:i') === '11:00'
                ) {
                    $itinerary->getCheckindate()->setTime(0, 0);
                    $itinerary->getCheckoutdate()->setTime(0, 0);
                }
            }
        }
    }

    private function checkItinerary(Itinerary $itinerary, string $method, ?LifecycleEventArgs $args = null)
    {
        $extraData = ['_itinerary_method' => $method];

        if ($itinerary instanceof Rental) {
            if (!$args) {
                return $this->checkDates(
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getPickupdatetime(), $itinerary->getPickupgeotagid()),
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getDropoffdatetime(), $itinerary->getDropoffgeotagid()),
                    $itinerary,
                    $extraData
                );
            }

            $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($itinerary);
            $rentalFields = ['pickupdatetime', 'dropoffdatetime', 'pickupgeotagid', 'dropoffgeotagid'];

            if (count($changes = array_intersect_key($changeSet, array_flip($rentalFields))) > 0) {
                return $this->checkDates(
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getPickupdatetime(), $itinerary->getPickupgeotagid()),
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getDropoffdatetime(), $itinerary->getDropoffgeotagid()),
                    $itinerary,
                    array_merge($extraData, ['_itinerary_changes' => $this->prepareChanges($changes)])
                );
            }
        } elseif ($itinerary instanceof Reservation) {
            if (!$args) {
                return $this->checkDates(
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getCheckindate(), $itinerary->getGeotagid()),
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getCheckoutdate(), $itinerary->getGeotagid()),
                    $itinerary,
                    $extraData
                );
            }

            $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($itinerary);
            $reservationFields = ['checkindate', 'checkoutdate', 'geotagid'];

            if (count($changes = array_intersect_key($changeSet, array_flip($reservationFields))) > 0) {
                return $this->checkDates(
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getCheckindate(), $itinerary->getGeotagid()),
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getCheckoutdate(), $itinerary->getGeotagid()),
                    $itinerary,
                    array_merge($extraData, ['_itinerary_changes' => $this->prepareChanges($changes)])
                );
            }
        } elseif ($itinerary instanceof Restaurant) {
            if (!$args) {
                return $this->checkDates(
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getStartDate(), $itinerary->getGeotagid()),
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getEndDate(), $itinerary->getGeotagid()),
                    $itinerary,
                    $extraData
                );
            }

            $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($itinerary);
            $restaurantFields = ['startdate', 'enddate', 'geotagid'];

            if (count($changes = array_intersect_key($changeSet, array_flip($restaurantFields))) > 0) {
                return $this->checkDates(
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getStartDate(), $itinerary->getGeotagid()),
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getEndDate(), $itinerary->getGeotagid()),
                    $itinerary,
                    array_merge($extraData, ['_itinerary_changes' => $this->prepareChanges($changes)])
                );
            }
        } elseif ($itinerary instanceof Parking) {
            if (!$args) {
                return $this->checkDates(
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getStartDate(), $itinerary->getGeoTagID()),
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getEndDate(), $itinerary->getGeoTagID()),
                    $itinerary,
                    $extraData
                );
            }

            $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($itinerary);
            $parkingFields = ['startdatetime', 'enddatetime', 'geotagid'];

            if (count($changes = array_intersect_key($changeSet, array_flip($parkingFields))) > 0) {
                return $this->checkDates(
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getStartDate(), $itinerary->getGeoTagID()),
                    Geotag::getLocalDateTimeByGeoTag($itinerary->getEndDate(), $itinerary->getGeoTagID()),
                    $itinerary,
                    array_merge($extraData, ['_itinerary_changes' => $this->prepareChanges($changes)])
                );
            }
        } elseif ($itinerary instanceof Trip) {
            if (!$args) {
                foreach ($itinerary->getSegments() as $segment) {
                    if (
                        !$this->checkDates(
                            Geotag::getLocalDateTimeByGeoTag($segment->getDepartureDate(), $segment->getDepgeotagid()),
                            Geotag::getLocalDateTimeByGeoTag($segment->getArrivalDate(), $segment->getArrgeotagid()),
                            $itinerary,
                            $extraData
                        )
                    ) {
                        break;
                    }
                }
            } else {
                $tripFields = ['depdate', 'arrdate', 'depgeotagid', 'arrgeotagid'];

                foreach ($itinerary->getSegments() as $segment) {
                    $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($segment);

                    if (count($changes = array_intersect_key($changeSet, array_flip($tripFields))) > 0) {
                        if (
                            !$this->checkDates(
                                Geotag::getLocalDateTimeByGeoTag($segment->getDepartureDate(), $segment->getDepgeotagid()),
                                Geotag::getLocalDateTimeByGeoTag($segment->getArrivalDate(), $segment->getArrgeotagid()),
                                $itinerary,
                                array_merge($extraData, ['_itinerary_changes' => $this->prepareChanges($changes)])
                            )
                        ) {
                            break;
                        }
                    }
                }
            }
        }

        return true;
    }

    private function prepareChanges(array $changes): array
    {
        $newChanges = [];

        foreach ($changes as $prop => $_changes) {
            foreach ($_changes as $k => $v) {
                if ($v instanceof \DateTime) {
                    $newChanges[$prop][$k] = $v->format('Y-m-d H:i:s');
                } elseif ($v instanceof Geotag) {
                    $newChanges[$prop][$k] = sprintf('geotag #%s, %s', $v->getGeotagid() ?? 'none', $v->getAddress());
                } else {
                    $newChanges[$prop][$k] = $v;
                }
            }
        }

        return $newChanges;
    }

    private function checkDates(?\DateTime $start, ?\DateTime $end, Itinerary $itinerary, array $extraData = []): bool
    {
        if (isset($start, $end) && $start->getTimestamp() > $end->getTimestamp()) {
            $this->logger->info('End date cannot precede the start date', array_merge([
                '_itinerary_id' => $itinerary->getId(),
                '_itinerary_kind' => $itinerary->getKind(),
                '_itinerary_modified' => $itinerary->getModified(),
                '_itinerary_parsed' => $itinerary->getParsed(),
                '_itinerary_start_date_ts' => $start->getTimestamp(),
                '_itinerary_start_date' => $start->format('Y-m-d H:i:s'),
                '_itinerary_start_date_tz' => $start->getTimezone()->getName(),
                '_itinerary_end_date_ts' => $end->getTimestamp(),
                '_itinerary_end_date' => $end->format('Y-m-d H:i:s'),
                '_itinerary_end_date_tz' => $end->getTimezone()->getName(),
            ], $extraData));

            return false;
        }

        return true;
    }
}
