<?php

namespace AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldTogglerType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'field_toggler';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'submitData' => false,
        ]);
    }
}
