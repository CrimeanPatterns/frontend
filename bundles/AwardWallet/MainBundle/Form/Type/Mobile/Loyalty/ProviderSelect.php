<?php

namespace AwardWallet\MainBundle\Form\Type\Mobile\Loyalty;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProviderSelect extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'provider_select';
    }
}
