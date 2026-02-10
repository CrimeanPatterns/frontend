<?php

namespace AwardWallet\MainBundle\Service\VisitedCountries;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary;
use AwardWallet\MainBundle\Timeline\Item\AbstractLayover;
use AwardWallet\MainBundle\Timeline\Item\AbstractParking;
use AwardWallet\MainBundle\Timeline\Item\AbstractRental;
use AwardWallet\MainBundle\Timeline\Item\AbstractTrip;
use AwardWallet\MainBundle\Timeline\Item\Checkin;
use AwardWallet\MainBundle\Timeline\Item\Checkout;
use AwardWallet\MainBundle\Timeline\Item\Date;
use AwardWallet\MainBundle\Timeline\Item\Event;
use AwardWallet\MainBundle\Timeline\Item\LayoverInterface;
use AwardWallet\MainBundle\Timeline\Item\PlanInterface;
use AwardWallet\MainBundle\Timeline\Manager;
use AwardWallet\MainBundle\Timeline\QueryOptions;

class Reporter
{
    private Manager $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return Period[]
     */
    public function getCountries(Usr $user, ?Useragent $agent = null, ?\DateTime $after = null, ?\DateTime $before = null): array
    {
        $queryOptions = (new QueryOptions())
            ->setUser($user)
            ->setWithDetails(false)
            ->setShowDeleted(false)
            ->setEndDate(new \DateTime())
        ;

        if (!is_null($agent)) {
            $queryOptions->setUserAgent($agent);
        }

        if (!is_null($after)) {
            $queryOptions->setStartDate($after);
        }

        if (!is_null($before)) {
            $queryOptions->setEndDate($before);
        }

        $timeline = $this->manager->queryWithoutMagic($queryOptions);
        $opened = null;
        $countryName = null;
        $countryCode = null;
        $dates = [];
        $result = [];

        $handleItem = function (Geotag $geotag, ?\DateTime $endDate = null) use (&$result, &$countryCode, &$countryName, &$dates) {
            if (isset($countryCode, $countryName) && count($dates) > 0) {
                $result[] = new Period(
                    $countryName,
                    $dates[0],
                    $endDate ? $this->prepareDate($endDate) : $dates[count($dates) - 1]
                );
            }
            $countryName = $geotag->getCountry();
            $countryCode = strtolower($geotag->getCountryCode());
            $dates = [];
        };

        $addDate = function (\DateTime $date) use (&$dates) {
            $dates[] = $this->prepareDate($date);
        };

        $countryChanged = function (Geotag $geotag) use (&$countryCode): bool {
            return strtolower($geotag->getCountryCode()) !== $countryCode;
        };

        foreach ($timeline as $k => $item) {
            /** @var AbstractItinerary $item */
            if (
                $item instanceof PlanInterface
                || $item instanceof Date
                || $item instanceof AbstractRental
                || $item instanceof AbstractParking
                || $item instanceof LayoverInterface
            ) {
                continue;
            }

            $source = $item->getSource();

            if (
                ($source instanceof Tripsegment && $source->getTripid()->getCancelled())
                || ($source instanceof Itinerary && $source->getCancelled())
            ) {
                continue;
            }

            $nextItem = $timeline[$k + 1] ?? null;

            if (isset($nextItem) && $nextItem->getStartDate() == $item->getStartDate() && $nextItem->getType() === $item->getType()) {
                continue;
            }

            if ($item instanceof AbstractTrip) {
                if ($this->notEmptyGeotag($startGt = $source->getDepgeotagid())) {
                    if ($countryChanged($startGt) && !$this->hasPrevLayover($timeline, $k - 1)) {
                        $handleItem($startGt);
                    }

                    if (!$countryChanged($startGt)) {
                        $addDate($item->getStartDate());
                    }
                }

                if ($this->notEmptyGeotag($endGt = $source->getArrgeotagid())) {
                    if (
                        $countryChanged($endGt)
                        && !empty($item->getEndDate())
                        && !$this->hasNextLayover($timeline, $k + 1)
                    ) {
                        $handleItem($endGt);
                    }

                    if (!$countryChanged($endGt) && !empty($item->getEndDate())) {
                        $addDate($item->getEndDate());
                    }
                }
            } elseif ($item instanceof Checkin) {
                if ($this->notEmptyGeotag($gt = $source->getGeotagid())) {
                    if ($countryChanged($gt)) {
                        $handleItem($gt, !is_null($opened) ? $item->getStartDate() : null);
                    }

                    $opened = $source;
                    $addDate($item->getStartDate());
                }
            } elseif ($item instanceof Checkout) {
                if ($this->notEmptyGeotag($gt = $source->getGeotagid()) && $opened === $source && !$countryChanged($gt)) {
                    $addDate($item->getStartDate());
                    $opened = null;
                }
            } elseif ($item instanceof Event) {
                if ($this->notEmptyGeotag($gt = $source->getGeotagid())) {
                    if ($countryChanged($gt)) {
                        $handleItem($gt);
                    }

                    $addDate($item->getStartDate());

                    if (!empty($item->getEndDate())) {
                        $addDate($item->getEndDate());
                    }
                }
            }
        }

        if (isset($countryCode, $countryName) && count($dates) > 0) {
            $result[] = new Period(
                $countryName,
                $dates[0],
                $dates[count($dates) - 1]
            );
        }

        return $result;
    }

    /**
     * @param AbstractItinerary[] $items
     */
    private function hasNextLayover(array $items, int $startKey): bool
    {
        for ($i = $startKey; isset($items[$i]); $i++) {
            $item = $items[$i];

            if ($item instanceof PlanInterface || $item instanceof Date) {
                continue;
            }

            if ($item instanceof AbstractLayover) {
                return true;
            } else {
                break;
            }
        }

        return false;
    }

    /**
     * @param AbstractItinerary[] $items
     */
    private function hasPrevLayover(array $items, int $startKey): bool
    {
        for ($i = $startKey; isset($items[$i]); $i--) {
            $item = $items[$i];

            if ($item instanceof PlanInterface || $item instanceof Date) {
                continue;
            }

            if ($item instanceof AbstractLayover) {
                return true;
            } else {
                break;
            }
        }

        return false;
    }

    private function notEmptyGeotag(?Geotag $geotag): bool
    {
        return $geotag && !empty($geotag->getCountryCode()) && !empty($geotag->getCountry());
    }

    private function prepareDate(\DateTime $dateTime): \DateTime
    {
        return clone $dateTime;
    }
}
