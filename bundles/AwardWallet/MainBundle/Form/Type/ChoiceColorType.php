<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ChoiceColorType extends ChoiceType
{
    public function getBlockPrefix()
    {
        return 'choice_color';
    }
}
