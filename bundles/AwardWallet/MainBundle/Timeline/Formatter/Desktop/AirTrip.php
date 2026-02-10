<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Desktop;

use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use AwardWallet\MainBundle\Timeline\Item\AbstractItinerary as AbstractItineraryItem;
use AwardWallet\MainBundle\Timeline\Item\AirTrip as AirTripItem;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\QueryOptions;

class AirTrip extends AbstractTrip
{
    public function format(ItemInterface $item, QueryOptions $queryOptions)
    {
        /** @var AirTripItem $item */
        $result = parent::format($item, $queryOptions);

        $result['air'] = true;
        $result['transferFormat'] = true;
        $result['duration'] = $item->getContext()->getPropFormatter()->getValue(PropertiesList::DURATION);

        if ($item->isMonitoringLowPrices()) {
            $result['lowPriceMonitoring'] = true;
        }

        return $result;
    }

    /**
     * @param AirTripItem $item
     */
    protected function getDetails(AbstractItineraryItem $item): array
    {
        $result = parent::getDetails($item);

        $result['bookingLink'] = [
            'formFields' => $item->getBookingFormFields(),
        ];

        return $result;
    }

    /**
     * @param AirTripItem $item
     */
    protected function getTitle(AbstractItineraryItem $item): ?string
    {
        $resolvedTripInfo = $this->tripHelper->resolveFlightName($item);
        $persistedTripInfo = $item->getTripInfo();

        $title = [];
        $title['companyCode'] = StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyCode ?? null)
            ? "{$persistedTripInfo->primaryTripNumberInfo->companyInfo->companyCode}"
            : '';

        if (
            empty($title['companyCode'])
            && StringUtils::isNotEmpty($resolvedTripInfo->getIataCode() ?? null)
            && StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName ?? null)
            && $resolvedTripInfo->getAirlineName() === $persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName
        ) {
            $title['companyCode'] = "{$resolvedTripInfo->getIataCode()}";
        }

        if (!empty($title['companyCode'])) {
            $title['companyCode'] = sprintf('<span>%s</span>', $title['companyCode']);
        }

        if (StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->tripNumber ?? null)) {
            $title['tripNumber'] = $persistedTripInfo->primaryTripNumberInfo->tripNumber;
        } elseif (
            StringUtils::isNotEmpty($resolvedTripInfo->getFlightNumber() ?? null)
            && StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName ?? null)
            && $resolvedTripInfo->getAirlineName() === $persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName
        ) {
            $title['tripNumber'] = $resolvedTripInfo->getFlightNumber();
        }
        $title['companyName'] = StringUtils::isNotEmpty($persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName ?? null)
            ? $persistedTripInfo->primaryTripNumberInfo->companyInfo->companyName
            : '';

        if (isset($title['companyCode'], $title['tripNumber']) && !empty($title['companyCode']) && !empty($title['tripNumber'])) {
            $title['companyCode'] .= $title['tripNumber'];
            unset($title['tripNumber']);
        }

        return trim(implode(' ', $title));
    }
}
