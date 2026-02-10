<?php

namespace AwardWallet\MobileBundle\Form\Type\NewDesign;

use AwardWallet\MainBundle\FrameworkExtension\Translator\TransChoice;
use AwardWallet\MobileBundle\Form\Type\PasswordMaskType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $passData = '';
        $builder->add('email', EmailType::class, [
            'label' => 'login.email',
            'error_bubbling' => false,
            'attr' => [
                'textContentType' => 'emailAddress',
            ],
        ]);
        $builder->add('pass', PasswordMaskType::class, [
            'label' => 'login.pass',
            'invalid_message' => 'user.pass_equal',
            'data' => $passData,
            'attr' => \array_merge(
                $passAttr = [
                    'notice' => new TransChoice('user.password.minlength', 8, ['%count%' => 8], 'validators'),
                    'trim' => true,
                    'textContentType' => 'none',
                    'passwordRules' => 'required: upper; required: lower; required: digit; minlength: 8; maxlength: 32;',
                ],
                ['policy' => true]
            ),
            'trim' => true,
            'error_bubbling' => false,
        ]);
        $builder->add('firstname', TextType::class, [
            'label' => 'login.first',
            'error_bubbling' => false,
            'attr' => [
                'textContentType' => 'givenName',
            ],
        ]);
        $builder->add('lastname', TextType::class, [
            'label' => 'login.name',
            'error_bubbling' => false,
            'attr' => [
                'textContentType' => 'familyName',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $validationGroups = ['register', 'unique'];
        $resolver->setDefaults([
            'validation_groups' => $validationGroups,
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\Usr',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'newapp_new_user';
    }
}
