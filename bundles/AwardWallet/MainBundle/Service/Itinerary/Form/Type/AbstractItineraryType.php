<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\FormTypeHelper;
use Symfony\Component\Form\AbstractType;

abstract class AbstractItineraryType extends AbstractType
{
    protected FormTypeHelper $helper;

    public function __construct(FormTypeHelper $helper)
    {
        $this->helper = $helper;
    }
}
