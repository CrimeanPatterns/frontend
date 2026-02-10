<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\Loyalty;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BarcodeType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'compound' => false,
            'allow_extra_fields' => true,
            'multiple' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'barcode';
    }
}
