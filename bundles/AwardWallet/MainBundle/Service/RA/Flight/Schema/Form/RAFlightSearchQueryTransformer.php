<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema\Form;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Form\Transformer\AbstractModelTransformer;

class RAFlightSearchQueryTransformer extends AbstractModelTransformer
{
    /**
     * @param RAFlightSearchQuery $value
     * @return RAFlightSearchQueryModel
     */
    public function transform($value)
    {
        return (new RAFlightSearchQueryModel())
            ->setEntity($value)
            ->setId($value->getId())
            ->setFromAirports($this->arrayToString($value->getDepartureAirports()))
            ->setToAirports($this->arrayToString($value->getArrivalAirports()))
            ->setFromDate($value->getDepDateFrom())
            ->setToDate($value->getDepDateTo())
            ->setFlightClass($value->getFlightClass())
            ->setAdults($value->getAdults())
            ->setSearchInterval($value->getSearchInterval())
            ->setAutoSelectParsers($value->getAutoSelectParsers())
            ->setExcludeParsers($value->getExcludeParsersAsArray())
            ->setParsers($value->getParsersAsArray())
            ->setEconomyMilesLimit($value->getEconomyMilesLimit())
            ->setPremiumEconomyMilesLimit($value->getPremiumEconomyMilesLimit())
            ->setBusinessMilesLimit($value->getBusinessMilesLimit())
            ->setFirstMilesLimit($value->getFirstMilesLimit())
            ->setMaxTotalDuration($value->getMaxTotalDuration())
            ->setMaxSingleLayoverDuration($value->getMaxSingleLayoverDuration())
            ->setMaxTotalLayoverDuration($value->getMaxTotalLayoverDuration())
            ->setMaxStops($value->getMaxStops());
    }

    private function arrayToString(?array $array): ?string
    {
        if (empty($array)) {
            return null;
        }

        return implode(', ', array_map('trim', $array));
    }
}
