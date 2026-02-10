<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class Select2HiddenType extends Select2TypeAbstract
{
    protected function getWidget()
    {
        return HiddenType::class;
    }
}
