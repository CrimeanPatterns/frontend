<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractSegmentModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\TrainSegmentModel;

class TrainConverter extends AbstractTripConverter
{
    /**
     * @param AbstractSegmentModel|TrainSegmentModel $model
     */
    public function convertSegment(Tripsegment $segment, AbstractSegmentModel $model)
    {
        $this->baseConverter->convertSegment($segment, $model);
        $model->setCarrier(htmlspecialchars_decode($segment->getAirlineName()));
        $model->setRoute(htmlspecialchars_decode($segment->getFlightNumber()));
        $model->setDepartureStation($segment->getDepname());
        $model->setDepartureStationCode($segment->getDepcode());
        $model->setArrivalStation($segment->getArrname());
        $model->setArrivalStationCode($segment->getArrcode());
    }

    /**
     * @param AbstractSegmentModel|TrainSegmentModel $model
     */
    public function reverseConvertSegment(AbstractSegmentModel $model, Tripsegment $segment)
    {
        if (empty($model->getCarrier())) {
            throw new \LogicException('Trying to save train ride segment without carrier');
        }

        if (empty($model->getDepartureStation()) || empty($model->getArrivalStation())) {
            throw new \LogicException('Trying to save train ride without departure and/or arrival station names');
        }

        $this->baseConverter->reverseConvertSegment($model, $segment);
        $segment->setAirlineName($model->getCarrier());
        $segment->setFlightNumber($model->getRoute());
        $segment->setDepcode($model->getDepartureStationCode());
        $segment->setArrcode($model->getArrivalStationCode());

        if (!is_null($depStation = $model->getDepartureStation())) {
            $segment->setDepname($depStation);
            $segment->setDepgeotagid($this->helper->convertAddress2GeoTag($depStation));
        } else {
            $segment->setDepname(null);
            $segment->setDepgeotagid(null);
        }

        if (!is_null($arrStation = $model->getArrivalStation())) {
            $segment->setArrname($arrStation);
            $segment->setArrgeotagid($this->helper->convertAddress2GeoTag($arrStation));
        } else {
            $segment->setArrname(null);
            $segment->setArrgeotagid(null);
        }
    }

    protected function getSegmentModel(): AbstractSegmentModel
    {
        return new TrainSegmentModel();
    }

    protected function getCategory(): int
    {
        return Trip::CATEGORY_TRAIN;
    }
}
