<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Reservation;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\ReservationModel;

class ReservationConverter extends AbstractConverter implements ItineraryConverterInterface
{
    /**
     * @param Reservation|Itinerary $itinerary
     * @param ReservationModel|AbstractModel $model
     */
    public function convert(Itinerary $itinerary, AbstractModel $model)
    {
        $this->baseConverter->convert($itinerary, $model);
        $model->setHotelName(htmlspecialchars_decode($itinerary->getHotelname()));
        $model->setCheckInDate($itinerary->getCheckindate());
        $model->setCheckOutDate($itinerary->getCheckoutdate());
        $model->setAddress($itinerary->getAddress());
        $model->setPhone($itinerary->getPhone());
    }

    /**
     * @param ReservationModel|AbstractModel $model
     * @param Reservation|Itinerary $itinerary
     */
    public function reverseConvert(AbstractModel $model, Itinerary $itinerary)
    {
        if (empty($model->getHotelName())) {
            throw new \LogicException('Trying to save hotel reservation without hotel name');
        }

        if (is_null($model->getCheckInDate()) || is_null($model->getCheckOutDate())) {
            throw new \LogicException('Trying to save hotel reservation without check-in and/or check-out dates');
        }

        $this->baseConverter->reverseConvert($model, $itinerary);
        $itinerary->setHotelname($model->getHotelName());
        $itinerary->setCheckindate($model->getCheckInDate());
        $itinerary->setCheckoutdate($model->getCheckOutDate());

        if (!is_null($address = $model->getAddress())) {
            $itinerary->setAddress($address);
            $itinerary->setGeoTag($this->helper->convertAddress2GeoTag($address));
        } else {
            $itinerary->setAddress(null);
            $itinerary->setGeoTag(null);
        }

        $itinerary->setPhone($model->getPhone());
    }
}
