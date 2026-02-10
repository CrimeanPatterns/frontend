<?php

namespace AwardWallet\MobileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class MobileType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'mobile';
    }
}
