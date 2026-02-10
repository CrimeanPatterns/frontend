<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Rental;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\RentalModel;

class RentalConverter extends AbstractConverter implements ItineraryConverterInterface
{
    /**
     * @param Rental|Itinerary $itinerary
     * @param RentalModel|AbstractModel $model
     */
    public function convert(Itinerary $itinerary, AbstractModel $model)
    {
        $this->baseConverter->convert($itinerary, $model);
        $model->setRentalCompany(htmlspecialchars_decode($itinerary->getRentalCompanyName()));
        $model->setPickUpAddress($itinerary->getPickuplocation());
        $model->setPickUpDate($itinerary->getPickupdatetime());
        $model->setDropOffAddress($itinerary->getDropofflocation());
        $model->setDropOffDate($itinerary->getDropoffdatetime());
        $model->setPhone($itinerary->getPickupphone());
    }

    /**
     * @param RentalModel|AbstractModel $model
     * @param Rental|Itinerary $itinerary
     */
    public function reverseConvert(AbstractModel $model, Itinerary $itinerary)
    {
        if (empty($model->getPickUpAddress()) || empty($model->getDropOffAddress())) {
            throw new \LogicException('Trying to save car rental without pick-up or drop-off location');
        }

        if (is_null($model->getPickUpDate()) || is_null($model->getDropOffDate())) {
            throw new \LogicException('Trying to save car rental without pick-up or drop-off dates');
        }

        $this->baseConverter->reverseConvert($model, $itinerary);
        $itinerary->setRentalCompanyName($model->getRentalCompany());

        if (!is_null($address = $model->getPickUpAddress())) {
            $itinerary->setPickuplocation($address);
            $itinerary->setPickupgeotagid($this->helper->convertAddress2GeoTag($address));
        } else {
            $itinerary->setPickuplocation(null);
            $itinerary->setPickupgeotagid(null);
        }

        $itinerary->setPickupdatetime($model->getPickUpDate());

        if (!is_null($address = $model->getDropOffAddress())) {
            $itinerary->setDropofflocation($address);
            $itinerary->setDropoffgeotagid($this->helper->convertAddress2GeoTag($address));
        } else {
            $itinerary->setDropofflocation(null);
            $itinerary->setDropoffgeotagid(null);
        }

        $itinerary->setDropoffdatetime($model->getDropOffDate());
        $itinerary->setPickupphone($model->getPhone());
        $itinerary->setType(Rental::TYPE_RENTAL);
    }
}
