<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DescType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required' => false,
            'mapped' => false,
            'submitData' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'desc';
    }
}
