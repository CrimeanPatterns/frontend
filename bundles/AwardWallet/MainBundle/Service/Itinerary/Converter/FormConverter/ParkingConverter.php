<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Parking;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\ParkingModel;

class ParkingConverter extends AbstractConverter implements ItineraryConverterInterface
{
    /**
     * @param Parking|Itinerary $itinerary
     * @param ParkingModel|AbstractModel $model
     */
    public function convert(Itinerary $itinerary, AbstractModel $model)
    {
        $this->baseConverter->convert($itinerary, $model);
        $model->setParkingCompanyName(htmlspecialchars_decode($itinerary->getParkingCompanyName()));
        $model->setAddress($itinerary->getLocation());
        $model->setStartDate($itinerary->getStartDatetime());
        $model->setEndDate($itinerary->getEndDatetime());
        $model->setPhone($itinerary->getPhone());
        $model->setPlate($itinerary->getPlate());
        $model->setSpot($itinerary->getSpot());
    }

    /**
     * @param ParkingModel|AbstractModel $model
     * @param Parking|Itinerary $itinerary
     */
    public function reverseConvert(AbstractModel $model, Itinerary $itinerary)
    {
        if (empty($model->getAddress())) {
            throw new \LogicException('Trying to save parking reservation without location');
        }

        if (is_null($model->getStartDate()) || is_null($model->getEndDate())) {
            throw new \LogicException('Trying to save parking reservation without start or end dates');
        }

        $this->baseConverter->reverseConvert($model, $itinerary);
        $itinerary->setParkingCompanyName($model->getParkingCompanyName());

        if (!is_null($address = $model->getAddress())) {
            $itinerary->setLocation($address);
            $itinerary->setGeoTagID($this->helper->convertAddress2GeoTag($address));
        } else {
            $itinerary->setLocation(null);
            $itinerary->setGeoTagID(null);
        }

        $itinerary->setStartDatetime($model->getStartDate());
        $itinerary->setEndDatetime($model->getEndDate());
        $itinerary->setPhone($model->getPhone());
        $itinerary->setPlate($model->getPlate());
        $itinerary->setSpot($model->getSpot());
    }
}
