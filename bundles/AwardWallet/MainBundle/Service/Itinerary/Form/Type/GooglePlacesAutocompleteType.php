<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class GooglePlacesAutocompleteType extends AbstractType
{
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * @deprecated
     */
    public function getBlockPrefix()
    {
        return 'places_autocomplete';
    }
}
