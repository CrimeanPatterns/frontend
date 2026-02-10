<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class Select2ChoiceType extends Select2TypeAbstract
{
    protected function getWidget()
    {
        return ChoiceType::class;
    }
}
