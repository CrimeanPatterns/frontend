<?php

namespace AwardWallet\MobileBundle\Form\Type;

class PasswordEditType extends PasswordMaskType
{
    public function getBlockPrefix()
    {
        return 'passwordEdit';
    }
}
