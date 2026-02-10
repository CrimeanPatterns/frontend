<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Restaurant;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\EventModel;

class EventConverter extends AbstractConverter implements ItineraryConverterInterface
{
    /**
     * @param Restaurant|Itinerary $itinerary
     * @param EventModel|AbstractModel $model
     */
    public function convert(Itinerary $itinerary, AbstractModel $model)
    {
        $this->baseConverter->convert($itinerary, $model);
        $model->setEventType($itinerary->getEventtype());
        $model->setTitle(htmlspecialchars_decode($itinerary->getName()));
        $model->setStartDate($itinerary->getStartDate());
        $model->setEndDate($itinerary->getEndDate());
        $model->setAddress($itinerary->getAddress());
        $model->setPhone($itinerary->getPhone());
    }

    /**
     * @param EventModel|AbstractModel $model
     * @param Restaurant|Itinerary $itinerary
     */
    public function reverseConvert(AbstractModel $model, Itinerary $itinerary)
    {
        if (empty($model->getTitle())) {
            throw new \LogicException('Trying to save event without title');
        }

        if (is_null($model->getStartDate())) {
            throw new \LogicException('Trying to save event without start date');
        }

        $this->baseConverter->reverseConvert($model, $itinerary);
        $itinerary->setEventtype($model->getEventType());
        $itinerary->setName($model->getTitle());
        $itinerary->setStartdate($model->getStartDate());
        $itinerary->setEnddate($model->getEndDate());

        if (!is_null($address = $model->getAddress())) {
            $itinerary->setAddress($address);
            $itinerary->setGeotagid($this->helper->convertAddress2GeoTag($address));
        } else {
            $itinerary->setAddress(null);
            $itinerary->setGeotagid(null);
        }

        $itinerary->setPhone($model->getPhone());
    }
}
