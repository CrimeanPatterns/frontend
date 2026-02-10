<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractSegmentModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\FlightSegmentModel;

class FlightConverter extends AbstractTripConverter
{
    private AirlineRepository $airlineRepository;

    public function __construct(
        BaseConverter $baseConverter,
        Helper $helper,
        AirlineRepository $airlineRepository
    ) {
        parent::__construct($baseConverter, $helper);
        $this->airlineRepository = $airlineRepository;
    }

    /**
     * @param AbstractSegmentModel|FlightSegmentModel $model
     */
    public function convertSegment(Tripsegment $segment, AbstractSegmentModel $model)
    {
        $this->baseConverter->convertSegment($segment, $model);
        $model->setAirlineName($segment->getAirlineName());
        $model->setFlightNumber($segment->getFlightNumber());
        $model->setDepartureAirport($segment->getDepartureAirport());
        $model->setArrivalAirport($segment->getArrivalAirport());
    }

    /**
     * @param AbstractSegmentModel|FlightSegmentModel $model
     */
    public function reverseConvertSegment(AbstractSegmentModel $model, Tripsegment $segment)
    {
        if (empty($model->getFlightNumber())) {
            throw new \LogicException('Flight number must be set before saving flight segment');
        }

        if (empty($model->getAirlineName())) {
            throw new \LogicException('Airline name must be set before saving flight segment');
        }

        if (is_null($model->getDepartureAirport()) || is_null($model->getArrivalAirport())) {
            throw new \LogicException('Departure and arrival airports must both be set before saving flight segment');
        }

        $this->baseConverter->reverseConvertSegment($model, $segment);
        $segment->setAirlineName($model->getAirlineName());
        $segment->setFlightNumber($model->getFlightNumber());

        if (!empty($airlineName = $model->getAirlineName())) {
            /**
             * Need to remove the hidden logic of the set*AirlineName, set*Airline
             * This makes life very difficult. Please somebody remove this shit!!!
             */
            $segment->setAirline($this->airlineRepository->findOneBy(['name' => $airlineName]), false);
        }

        $segment->setDepartureAirport($model->getDepartureAirport());
        $segment->setArrivalAirport($model->getArrivalAirport());
    }

    protected function getSegmentModel(): AbstractSegmentModel
    {
        return new FlightSegmentModel();
    }

    protected function getCategory(): int
    {
        return Trip::CATEGORY_AIR;
    }
}
