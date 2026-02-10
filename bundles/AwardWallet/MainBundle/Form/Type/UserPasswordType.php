<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Validator\Constraints\AndX;
use AwardWallet\MainBundle\Validator\Constraints\AntiBruteforceLocker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserPasswordType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'allow_master_password',
            'user_login',
            'user_ip',
        ]);

        $resolver->setDefaults([
            'label' => /** @Desc("AwardWallet password") */ 'aw.password',
            'mapped' => false,
            'required' => true,
            'allow_tags' => true,
            'allow_quotes' => true,
            'allow_urls' => true,
            'allow_master_password' => false,
            'constraints' => function (Options $options) {
                return [
                    new AndX([
                        new NotBlank(),
                        new AntiBruteforceLocker([
                            'service' => 'aw.security.antibruteforce.password',
                            'keyMethod' => function () use ($options) {
                                return $options->offsetExists('user_login') ? strtolower($options['user_login']) : $options['user_ip'];
                            },
                        ]),
                        new UserPassword([
                            'message' => 'invalid.password',
                            'service' => $options['allow_master_password'] ?
                                'aw.form.validator.user_password.allow_master_pass' : 'aw.form.validator.user_password.disallow_master_pass',
                        ]),
                    ]),
                ];
            },
            'attr' => [
                'autocomplete' => 'off',
                'autocorrect' => 'off',
                'autocapitalize' => 'off',
            ],
        ]);
    }

    public function getBlockPrefix()
    {
        return 'user_pass';
    }

    public function getParent()
    {
        return PasswordType::class;
    }
}
