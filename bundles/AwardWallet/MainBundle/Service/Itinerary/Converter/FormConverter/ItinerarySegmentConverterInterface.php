<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractSegmentModel;

interface ItinerarySegmentConverterInterface
{
    public function convertSegment(Tripsegment $segment, AbstractSegmentModel $model);

    public function reverseConvertSegment(AbstractSegmentModel $model, Tripsegment $segment);
}
