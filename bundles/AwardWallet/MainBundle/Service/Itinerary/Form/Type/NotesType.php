<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class NotesType extends AbstractType
{
    public function getParent()
    {
        return TextareaType::class;
    }
}
