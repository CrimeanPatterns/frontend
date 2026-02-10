<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractModel;

interface ItineraryConverterInterface
{
    public function convert(Itinerary $itinerary, AbstractModel $model);

    public function reverseConvert(AbstractModel $model, Itinerary $itinerary);
}
