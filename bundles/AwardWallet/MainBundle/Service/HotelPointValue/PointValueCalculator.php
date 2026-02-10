<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use AwardWallet\Common\CurrencyConverter\CurrencyConverter;
use AwardWallet\MainBundle\Entity\HotelPointValue;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class PointValueCalculator
{
    private SpentAwardsFilter $spentAwardsFilter;

    private PriceFinder $priceFinder;

    private CurrencyConverter $currencyConverter;

    private EntityManagerInterface $em;

    private Process $asyncProcess;

    private ContextAwareLoggerWrapper $logger;

    private SearchValidator $searchValidator;

    private BrandMatcher $brandMatcher;

    private AutoReviewer $autoReviewer;

    public function __construct(
        SpentAwardsFilter $spentAwardsFilter,
        PriceFinder $priceFinder,
        CurrencyConverter $currencyConverter,
        EntityManagerInterface $em,
        Process $asyncProcess,
        LoggerInterface $logger,
        SearchValidator $searchValidator,
        BrandMatcher $brandMatcher,
        AutoReviewer $autoReviewer
    ) {
        $this->spentAwardsFilter = $spentAwardsFilter;
        $this->priceFinder = $priceFinder;
        $this->currencyConverter = $currencyConverter;
        $this->em = $em;
        $this->asyncProcess = $asyncProcess;
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->pushContext(['class' => 'PointValueCalculator']);
        $this->searchValidator = $searchValidator;
        $this->brandMatcher = $brandMatcher;
        $this->autoReviewer = $autoReviewer;
    }

    public function updateItinerary(Reservation $reservation, bool $async): void
    {
        $this->logger->pushContext(["reservationId" => $reservation->getId()]);

        try {
            if ($reservation->getProvider() === null) {
                return;
            }

            if ($reservation->getGeotagid() === null || $reservation->getGeotagid()->getLat() === null) {
                return;
            }

            $spentAwards = $this->spentAwardsFilter->filter($reservation->getPricingInfo()->getSpentAwards());

            $valid = true;

            if ($spentAwards === null) {
                $this->logger->info("no SpentAwards, will not calc hpv");
                $valid = false;
            }

            if ($valid && $spentAwards <= 0) {
                $this->logger->info("SpentAwards is zero or lower than zero, will not calc hpv");
                $valid = false;
            }

            if ($valid && $reservation->getGuestCount() === null) {
                $this->logger->info("Guest Count is null, will not calc hpv");
                $valid = false;
            }

            if (!$valid) {
                if ($reservation->getHotelPointValue() !== null) {
                    $this->logger->info("deleting invalid hpv record");
                    $this->em->remove($reservation->getHotelPointValue());
                    $this->em->flush();
                    $this->em->refresh($reservation);
                }

                return;
            }

            $params = new PointValueParams($reservation, $spentAwards,
                $this->brandMatcher->match($reservation->getHotelname(), $reservation->getProvider()->getId()));
            $hotelPointValue = $reservation->getHotelPointValue();
            $canSearchPrices = $this->searchValidator->canSearchPrices($reservation) && $spentAwards > 0;

            if ($hotelPointValue === null && !$canSearchPrices) {
                $this->logger->info("will not calc hpv, can't search new prices");

                return;
            }

            if ($hotelPointValue === null && $async) {
                $this->logger->info("HotelPointValue not found, will do async search");
                $this->asyncProcess->execute(new UpdateTask($reservation->getId()));

                return;
            }

            if ($hotelPointValue === null) {
                $this->createNewPointValue($reservation, $params);
                $this->em->flush();

                return;
            }

            $wantSearchPrices = $hotelPointValue->getHash() !== $params->getHash();

            $this->updatePointValue($reservation, $hotelPointValue, $params);

            if ($canSearchPrices && $wantSearchPrices) {
                $this->logger->info("hash changed, will do price search");
                $this->searchPrice($params, $hotelPointValue);
            }

            $this->em->flush();
        } finally {
            $this->logger->popContext();
        }
    }

    private function createNewPointValue(Reservation $reservation, PointValueParams $params): void
    {
        $hpv = new HotelPointValue();
        $hpv->setReservation($reservation);

        if (!$this->searchPrice($params, $hpv)) {
            return;
        }

        $this->updatePointValue($reservation, $hpv, $params);

        $this->em->persist($hpv);
        $this->em->flush();
    }

    private function updatePointValue(Reservation $reservation, HotelPointValue $hpv, PointValueParams $params)
    {
        $totalTaxes = $this->currencyConverter->convertToUsd($params->getTotal(), $params->getCurrencyCode());

        if ($totalTaxes === null) {
            $this->logger->info("could not convert total to usd: " . $params->getTotal() . " " . $params->getCurrencyCode());

            return;
        }

        $hpv->setProvider($reservation->getProvider());
        $hpv->setTotalPointsSpent($params->getSpentAwards());
        $hpv->setTotalTaxesSpent($totalTaxes);
        $hpv->setAddress($reservation->getAddress());
        $hpv->setHotelName($reservation->getHotelname());
        $hpv->setBrand($params->getBrand());
        $hpv->setHash($params->getHash());
        $hpv->setCheckInDate($reservation->getCheckindate());
        $hpv->setCheckOutDate($reservation->getCheckoutdate());
        $hpv->setGuestCount($params->getGuestCount());
        $hpv->setRoomCount($params->getRoomsCount());
        $hpv->setKidsCount($params->getKidsCount());
        $hpv->setLatLng($params->getLat() . "," . $params->getLng());
        $hpv->setUpdateDate(new \DateTime());
        $hpv->setPointValue(round(($hpv->getAlternativeCost() - $hpv->getTotalTaxesSpent()) / $hpv->getTotalPointsSpent() * 100, 2));

        $autoReviewNote = $this->autoReviewer->check($hpv);

        if ($autoReviewNote !== null) {
            $hpv->setStatus(CalcMileValueCommand::STATUS_AUTO_REVIEW);
            $this->logger->info("marked by autoreview: " . $autoReviewNote);
            $hpv->setNote($autoReviewNote);
        }
    }

    private function searchPrice(PointValueParams $params, HotelPointValue $hpv): bool
    {
        $this->logger->info("searching prices for {$params->getHotelname()}, brand: " . ($params->getBrand() ? $params->getBrand()->getName() : "none") . " at {$hpv->getReservation()->getAddress()} / {$params->getLat()},{$params->getLng()} for {$params->getGuestCount()}/{$params->getKidsCount()} guests for {$params->getCheckindate()->format("Y-m-d")}:{$params->getCheckoutdate()->format("Y-m-d")}, {$params->getSpentAwards()}, {$params->getTotal()}");
        $price = $this->priceFinder->search($params);

        if ($price === null) {
            return false;
        }

        $hpv->setAlternativeCost($price->getTotal());
        $hpv->setAlternativeLatLng($price->getLat() . ',' . $price->getLng());
        $hpv->setAlternativeHotelUrl($price->getHotelUrl());
        $hpv->setAlternativeBookingUrl($price->getBookingUrl());
        $hpv->setAlternativeHotelName($price->getHotelName());

        return true;
    }
}
