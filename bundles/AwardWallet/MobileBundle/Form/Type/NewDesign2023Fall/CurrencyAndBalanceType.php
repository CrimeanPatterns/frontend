<?php

namespace AwardWallet\MobileBundle\Form\Type\NewDesign2023Fall;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencyAndBalanceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => true,
            'required' => false,
            'compound' => true,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'currency_and_balance';
    }
}
