<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Converter\FormConverter;

use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;

abstract class AbstractConverter
{
    protected BaseConverter $baseConverter;

    protected Helper $helper;

    public function __construct(BaseConverter $baseConverter, Helper $helper)
    {
        $this->baseConverter = $baseConverter;
        $this->helper = $helper;
    }
}
