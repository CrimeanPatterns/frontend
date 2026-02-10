<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractSegmentModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\TripModel;
use AwardWallet\MainBundle\Timeline\PlanManager;

class BaseConverter implements ItineraryConverterInterface, ItinerarySegmentConverterInterface
{
    public function convert(Itinerary $itinerary, AbstractModel $model)
    {
        $model->setOwner($itinerary->getOwner());
        $model->setConfirmationNumber($itinerary->getConfirmationNumber());
        $model->setNotes($this->cleanNotes($itinerary->getNotes()));

        if ($itinerary instanceof Trip && $model instanceof TripModel) {
            $model->setPhone($itinerary->getPhone());
        }
    }

    public function reverseConvert(AbstractModel $model, Itinerary $itinerary)
    {
        if (is_null($model->getOwner())) {
            throw new \LogicException('Trying to save itinerary without owner');
        }

        $itinerary->setOwner($model->getOwner());
        $itinerary->setConfirmationNumber($model->getConfirmationNumber());
        $itinerary->setNotes($this->cleanNotes($model->getNotes()));

        if ($itinerary instanceof Trip && $model instanceof TripModel) {
            $itinerary->setPhone($model->getPhone());
        }
    }

    public function convertSegment(Tripsegment $segment, AbstractSegmentModel $model)
    {
        $model->setTripSegment($segment);
        $model->setDepartureDate($segment->getDepartureDate());
        $model->setArrivalDate($segment->getArrivalDate());
    }

    public function reverseConvertSegment(AbstractSegmentModel $model, Tripsegment $segment)
    {
        if (is_null($model->getDepartureDate()) || is_null($model->getArrivalDate())) {
            throw new \LogicException('departureDate and arrivalDate must both be set before saving trip segment');
        }

        $segment->setDepartureDate($model->getDepartureDate());
        $segment->setScheduledDepDate($model->getDepartureDate());
        $segment->setArrivalDate($model->getArrivalDate());
        $segment->setScheduledArrDate($model->getArrivalDate());
        $segment->setChangeDate(new \DateTime());
    }

    private function cleanNotes(?string $notes): ?string
    {
        $notes = StringHandler::cleanHtmlText($notes);

        return PlanManager::cleanBlankLineInEndText($notes);
    }
}
