<?php

namespace AwardWallet\MobileBundle\Form\Type\NewDesign;

use AwardWallet\MainBundle\FrameworkExtension\Translator\TransChoice;
use AwardWallet\MobileBundle\Form\Type\PasswordMaskType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('pass', RepeatedType::class, [
            'type' => PasswordMaskType::class,
            'label' => 'login.pass',
            'invalid_message' => 'user.pass_equal',
            'first_options' => ['label' => 'login.pass', 'attr' => array_merge($passAttr = ['notice' => new TransChoice('user.password.minlength', 8, ['%count%' => 8], 'validators')], ['policy' => true])],
            'second_options' => ['label' => 'login.pass1'],
            'options' => [
                'attr' => $passAttr,
                'label_attr' => [
                    'data-error-label' => false,
                ],
                'trim' => true,
            ],
            'error_bubbling' => false,
        ]);
        $builder->add('login', HiddenType::class, ['disabled' => true]);
        $builder->add('email', HiddenType::class, ['disabled' => true]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['change_pass'],
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\Usr',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'newapp_change_pass';
    }
}
