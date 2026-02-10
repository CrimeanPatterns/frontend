<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Trip;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractModel;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractSegmentModel;
use Doctrine\Common\Collections\ArrayCollection;

abstract class AbstractTripConverter extends AbstractConverter implements ItineraryConverterInterface, ItinerarySegmentConverterInterface
{
    /**
     * @param Trip|Itinerary $itinerary
     */
    public function convert(Itinerary $itinerary, AbstractModel $model)
    {
        $this->baseConverter->convert($itinerary, $model);
        $segmentModels = new ArrayCollection();

        foreach ($itinerary->getVisibleSegments() as $tripSegment) {
            $this->convertSegment($tripSegment, $segmentModel = $this->getSegmentModel());
            $segmentModels->add($segmentModel);
        }

        $model->setSegments($segmentModels);
    }

    /**
     * @param Trip|Itinerary $itinerary
     */
    public function reverseConvert(AbstractModel $model, Itinerary $itinerary)
    {
        $itinerary->setCategory($this->getCategory());
        $this->baseConverter->reverseConvert($model, $itinerary);
        $segments = [];

        foreach ($model->getSegments() as $segmentModel) {
            $segment = $segmentModel->getTripSegment();

            if (is_null($segment)) {
                $segment = new Tripsegment();
            }

            $this->reverseConvertSegment($segmentModel, $segment);
            $segments[] = $segment;
            $itinerary->addSegment($segment);
        }

        $storedTripSegments = $itinerary->getSegments()->toArray();
        array_walk($storedTripSegments, function (Tripsegment $tripSegment) use ($segments) {
            if (!in_array($tripSegment, $segments)) {
                $tripSegment->hideByUser();
                $tripSegment->setUndeleted(false);
            }
        });

        // Otherwise it wont be saved to segments
        $itinerary->setConfirmationNumber($model->getConfirmationNumber());
    }

    abstract protected function getSegmentModel(): AbstractSegmentModel;

    abstract protected function getCategory(): int;
}
