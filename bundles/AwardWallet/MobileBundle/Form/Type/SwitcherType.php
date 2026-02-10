<?php

namespace AwardWallet\MobileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class SwitcherType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'switcher';
    }

    public function getParent()
    {
        return CheckboxType::class;
    }
}
