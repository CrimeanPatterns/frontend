<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractSegmentModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\CruiseSegmentModel;

class CruiseConverter extends AbstractTripConverter
{
    /**
     * @param AbstractSegmentModel|CruiseSegmentModel $model
     */
    public function convertSegment(Tripsegment $segment, AbstractSegmentModel $model)
    {
        $this->baseConverter->convertSegment($segment, $model);
        $model->setCruiseShip(htmlspecialchars_decode($segment->getAirlineName()));
        $model->setRoute(htmlspecialchars_decode($segment->getFlightNumber()));
        $model->setDeparturePort($segment->getDepname());
        $model->setArrivalPort($segment->getArrname());
    }

    /**
     * @param AbstractSegmentModel|CruiseSegmentModel $model
     */
    public function reverseConvertSegment(AbstractSegmentModel $model, Tripsegment $segment)
    {
        if (empty($model->getCruiseShip())) {
            throw new \LogicException('Trying to save cruise segment without a ship');
        }

        if (empty($model->getDeparturePort()) || empty($model->getArrivalPort())) {
            throw new \LogicException('Trying to save cruise segment without departure and/or arrival ports');
        }

        $this->baseConverter->reverseConvertSegment($model, $segment);
        $segment->setAirlineName($model->getCruiseShip());
        $segment->setFlightNumber($model->getRoute());

        if (!is_null($depPort = $model->getDeparturePort())) {
            $segment->setDepname($depPort);
            $segment->setDepgeotagid($this->helper->convertAddress2GeoTag($depPort));
        } else {
            $segment->setDepname(null);
            $segment->setDepgeotagid(null);
        }

        if (!is_null($arrPort = $model->getArrivalPort())) {
            $segment->setArrname($arrPort);
            $segment->setArrgeotagid($this->helper->convertAddress2GeoTag($arrPort));
        } else {
            $segment->setArrname(null);
            $segment->setArrgeotagid(null);
        }
    }

    protected function getSegmentModel(): AbstractSegmentModel
    {
        return new CruiseSegmentModel();
    }

    protected function getCategory(): int
    {
        return Trip::CATEGORY_CRUISE;
    }
}
