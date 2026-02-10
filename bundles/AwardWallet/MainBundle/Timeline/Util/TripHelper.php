<?php

namespace AwardWallet\MainBundle\Timeline\Util;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\OperatedByResolver;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary;
use AwardWallet\MainBundle\Timeline\Item\AirTrip;
use AwardWallet\MainBundle\Timeline\Item\BookingHotelForm;
use Clock\ClockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TripHelper
{
    private OperatedByResolver $operatedByResolver;
    private LocalizeService $localizeService;
    private TranslatorInterface $translator;
    private ClockInterface $clock;
    private $referralId;

    public function __construct(
        OperatedByResolver $operatedByResolver,
        LocalizeService $localizeService,
        TranslatorInterface $translator,
        ClockInterface $clock,
        $referralId
    ) {
        $this->operatedByResolver = $operatedByResolver;
        $this->localizeService = $localizeService;
        $this->translator = $translator;
        $this->referralId = $referralId;
        $this->clock = $clock;
    }

    public function resolveFlightName(AbstractItinerary $timelineSegment): ResolvedFlightName
    {
        $segment = $timelineSegment->getSource();
        $result = new ResolvedFlightName();
        $result->setAirlineName($this->resolveAirlineName($segment));
        $result->setFlightNumber($segment->getFlightNumber());
        $provider = $timelineSegment->getProvider() ?? $segment->getTripid()->getProvider();

        if (null !== $provider) {
            $result->setIataCode($provider->getIATACode());
        }

        return $result;
    }

    /**
     * @param AbstractItinerary[] $segments
     */
    public function fillBookingLinks(array $segments, ?Usr $timelineOwner = null)
    {
        /** @var AirTrip[] $airSegments */
        $airSegments = array_filter($segments, function ($segment) {
            return $segment instanceof AirTrip;
        });

        foreach ($airSegments as $segment) {
            $segment->setBookingInfo($this->getBookingInfo($segment, $airSegments));
            $segment->setBookingUrl($this->getBookingUrl($segment, $airSegments, $timelineOwner));
            $segment->setBookingFormFields($this->getFormFields($segment, $airSegments, $timelineOwner));
        }
    }

    /**
     * @param AirTrip[] $segments
     */
    private function getBookingUrl(AirTrip $segment, array $segments, ?Usr $timelineOwner = null): string
    {
        $arrivalDate = $this->getArrivalDate($segment);
        $nextSegment = $this->getNextSegment($segment, $segments);
        $nightsCount = $this->getNightsCount($segment, $nextSegment);

        if (!$nightsCount) {
            $nightsCount = 1;
        }

        if ($arrivalDate < $this->clock->current()->getAsDateTime()) {
            $arrivalDate = $this->clock->current()->getAsDateTime();
        }

        $checkoutDate = clone $arrivalDate;
        $checkoutDate->modify("+ {$nightsCount} day");

        $referralParams = [
            'aid' => $this->referralId,
            'iata' => $segment->getSource()->getArrcode(), // destination
            'iata_orr' => 3, // hardcoded
            'checkin_monthday' => $arrivalDate->format('d'),
            'checkin_year_month' => $arrivalDate->format('Y-m'),
            'checkout_monthday' => $checkoutDate->format('d'),
            'checkout_year_month' => $checkoutDate->format('Y-m'),
            'label' => 'dskTimeline_' . ($timelineOwner ? $timelineOwner->getRefcode() : ''), // refCode
        ];

        return 'https://awardwallet.com/blog/link/booking?' . http_build_query($referralParams);
    }

    /**
     * @param AirTrip[] $segments
     */
    private function getBookingInfo(AirTrip $segment, array $segments): string
    {
        $destination = $segment->getSource()->getArrAirportName(false);
        $arrivalDay = $this->localizeService->formatDateTime($this->getArrivalDate($segment), 'medium', null);
        $nextSegment = $this->getNextSegment($segment, $segments);
        $nightsCount = $this->getNightsCount($segment, $nextSegment);

        return $this->translator->trans(
            /** @Desc("{0}Check out these amazing hotel deals in %destination%|{1}Check out these amazing hotel deals in %destination% for %arrivalDay%, for 1 night|[2,14] Check out these amazing hotel deals in %destination% for %arrivalDay%, for %count% nights") */
            "timeline.booking.info",
            [
                '%count%' => $nightsCount,
                '%destination%' => $destination,
                '%arrivalDay%' => $arrivalDay,
            ]);
    }

    /**
     * @param AirTrip[] $segments
     */
    private function getFormFields(AirTrip $segment, array $segments, ?Usr $timelineOwner = null): BookingHotelForm
    {
        $destination = $segment->getSource()->getArrAirportName(false);
        $nextSegment = $this->getNextSegment($segment, $segments);
        $nightsCount = $this->getNightsCount($segment, $nextSegment);

        if (!$nightsCount) {
            $nightsCount = 1;
        }

        $arrivalDate = $this->getArrivalDate($segment);

        if ($arrivalDate < $this->clock->current()->getAsDateTime()) {
            $arrivalDate = $this->clock->current()->getAsDateTime();
        }

        $checkoutDate = clone $arrivalDate;
        $checkoutDate->modify("+ {$nightsCount} day");

        $referralParams = [
            'aid' => $this->referralId, // referral id
            'label' => 'dskTimelineForm_' . ($timelineOwner ? $timelineOwner->getRefcode() : ''), // refCode
        ];

        return new BookingHotelForm(
            $destination,
            $arrivalDate,
            $checkoutDate,
            'https://awardwallet.com/blog/link/booking?' . http_build_query($referralParams)
        );
    }

    /**
     * @param AirTrip $item
     */
    private function getArrivalDate($item): \DateTime
    {
        /** @var Tripsegment $source */
        $source = $item->getSource();
        $arrivalHour = $source->getArrivalDate()->format('H');
        $arrivalDate = clone $source->getArrivalDate();

        if ($arrivalHour >= 0 && $arrivalHour <= 4) {
            $arrivalDate->modify('-1 day');
        }

        return $arrivalDate;
    }

    /**
     * @param AirTrip[] $segments
     */
    private function getNextSegment(AirTrip $segment, array $segments): ?AirTrip
    {
        /** @var Tripsegment $source */
        $source = $segment->getSource();
        $nextSegments = array_filter($segments, function (AirTrip $item) use ($source) {
            /** @var Tripsegment $source */
            $currentSource = $item->getSource();

            return
                $currentSource->getUser() === $source->getUser()
                && $currentSource->getDepartureDate() > $source->getArrivalDate()
                && $currentSource->getDepcode() === $source->getArrcode()
            ;
        });

        return count($nextSegments) ? array_shift($nextSegments) : null;
    }

    private function getNightsCount(AirTrip $segment, ?AirTrip $nextSegment = null): int
    {
        $arrivalDate = clone $this->getArrivalDate($segment);
        $arrivalDate->setTime(0, 0, 0);

        $nightsCount = 1;

        if ($arrivalDate < $this->clock->current()->getAsDateTime()) {
            $nightsCount = 0;
        } elseif ($nextSegment) {
            $departureDate = clone $nextSegment->getSource()->getDepartureDate();
            $departureDate->setTime(0, 0, 0);

            $diffDays = date_diff($arrivalDate, $departureDate, true)->days;
            $nightsCount = $diffDays > 14 || $diffDays === 0 ? 1 : $diffDays;
        }

        return $nightsCount;
    }

    private function resolveAirlineName(Tripsegment $segment)
    {
        // From tripsegment
        $tripSegmentAirlineName = function () use ($segment) {
            if (empty($segment->getAirlineName())) {
                return null;
            }

            return $segment->getAirlineName();
        };
        // From trip
        $tripAirlineName = function () use ($segment) {
            if (empty($segment->getTripid()->getAirlineName())) {
                return null;
            }

            return $segment->getTripid()->getAirlineName();
        };
        // From provider
        $providerName = function () use ($segment) {
            if (null === ($provider = $segment->getTripid()->getProvider())) {
                return null;
            }

            if (empty($provider->getShortname())) {
                return null;
            }

            return $provider->getShortname();
        };
        // Deprecated way, should be removed in time
        $codeAirlineName = function () use ($segment) {
            if (!preg_match("/^(?'code'[a-zA-Z]{1,2})(?'number'\d+)$/", $segment->getFlightNumber(), $matches)) {
                return null;
            }
            /** @var Airline $airline */
            $airline = $this->operatedByResolver->getManager()->getRepository(\AwardWallet\MainBundle\Entity\Airline::class)->findOneBy(['code' => $matches['code']]);

            if (null !== $airline && !empty($airline->getName())) {
                return $airline->getName();
            }
        };

        return $tripSegmentAirlineName() ?? $tripAirlineName() ?? $providerName() ?? $codeAirlineName();
    }
}
