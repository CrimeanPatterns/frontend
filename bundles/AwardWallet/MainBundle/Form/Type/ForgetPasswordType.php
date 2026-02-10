<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForgetPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\Usr',
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'user_forget_password';
    }
}
