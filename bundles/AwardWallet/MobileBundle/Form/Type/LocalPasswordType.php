<?php

namespace AwardWallet\MobileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

class LocalPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'password',
            PasswordMaskType::class,
            [
                'required' => true,
                'error_bubbling' => false,
                'trim' => true,
                'constraints' => [
                    new Constraints\NotBlank(),
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'csrf_protection' => false,
            ]
        );
    }

    public function getBlockPrefix()
    {
        return 'local_password';
    }
}
