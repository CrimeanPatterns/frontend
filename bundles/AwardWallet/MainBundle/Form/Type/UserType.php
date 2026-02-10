<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $this->prepareFields($event, $options);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Usr::class,
            'validation_groups' => function (FormInterface $form) {
                /** @var Usr $user */
                $user = $form->getData();

                if ($user->isBusiness()) {
                    return ['register', 'unique', 'business_register'];
                }

                return ['register', 'unique'];
            },
            'error_bubbling' => false,
            /** @Ignore */
            'label' => false,
            'constraints' => new Valid(),
            'translation_domain' => 'messages',
            'booking_form' => false,
        ]);
        $resolver->setAllowedTypes('booking_form', 'bool');
    }

    public function getBlockPrefix()
    {
        return 'user';
    }

    private function prepareFields(FormEvent $event, array $options)
    {
        /** @var Usr $user */
        $user = $event->getData();
        $form = $event->getForm();

        if ($options['booking_form']) {
            $this->addFirstNameField($form);
            $this->addLastNameField($form);
            $this->addEmailField($form, true);
            $this->addPhoneField($form);
            $this->addLoginField($form);
            $this->addPasswordField($form, true);
        } elseif ($user->isBusiness()) {
            $this->addLoginField($form, true);
            $this->addPasswordField($form, true);
            $form->add('company', TextType::class, [
                'required' => true,
                'label' => 'business.name.field',
                'attr' => [
                    'autocomplete' => 'off',
                    'autocorrect' => 'off',
                    'autocapitalize' => 'off',
                ],
            ]);
            $this->addFirstNameField($form);
            $this->addLastNameField($form);
            $this->addEmailField($form);
            $form->add('picturever', FileType::class, [
                'required' => false,
                'label' => 'logo.field',
            ]);
        } else {
            $this->addEmailField($form);
            $this->addPasswordField($form, false);
            $this->addFirstNameField($form);
            $this->addLastNameField($form);
        }
    }

    private function addFirstNameField(FormInterface $form)
    {
        $form->add('firstname', TextType::class, [
            'required' => true,
            'label' => 'login.first',
            'attr' => [
                'autocomplete' => 'off',
                'autocorrect' => 'off',
                'autocapitalize' => 'off',
            ],
        ]);
    }

    private function addLastNameField(FormInterface $form)
    {
        $form->add('lastname', TextType::class, [
            'required' => true,
            'label' => 'login.name',
            'attr' => [
                'autocomplete' => 'off',
                'autocorrect' => 'off',
                'autocapitalize' => 'off',
            ],
        ]);
    }

    private function addEmailField(FormInterface $form, bool $repeated = false)
    {
        if ($repeated) {
            $form->add('email', RepeatedType::class, [
                'required' => true,
                'first_name' => 'Email',
                'second_name' => 'ConfirmEmail',
                'first_options' => ['label' => 'login.email'],
                'second_options' => ['label' => 'login.confirmemail'],
                'type' => EmailType::class,
                'invalid_message' => 'user.email_equal',
            ]);
        } else {
            $form->add('email', EmailType::class, [
                'required' => true,
                'label' => 'login.email',
                'attr' => [
                    'autocomplete' => 'off',
                    'autocorrect' => 'off',
                    'autocapitalize' => 'off',
                ],
            ]);
        }
    }

    private function addPhoneField(FormInterface $form)
    {
        $form->add('phone1', TextType::class, [
            'required' => true,
            'label' => 'booking.phone',
            'translation_domain' => 'booking',
        ]);
    }

    private function addLoginField(FormInterface $form, bool $autofocus = false)
    {
        $form->add('login', TextType::class, [
            'required' => true,
            'label' => 'login.login',
            'attr' => array_merge([
                'autocomplete' => 'off',
                'autocorrect' => 'off',
                'autocapitalize' => 'off',
            ], $autofocus ? ['autofocus' => 'on'] : []),
        ]);
    }

    private function addPasswordField(FormInterface $form, bool $repeated = false)
    {
        if ($repeated) {
            $form->add('pass', RepeatedType::class, [
                'required' => true,
                'first_name' => 'Password',
                'second_name' => 'ConfirmPassword',
                'first_options' => ['label' => 'login.pass'],
                'second_options' => ['label' => 'login.pass1'],
                'invalid_message' => 'user.pass_equal',
                'type' => PasswordType::class,
                'options' => [
                    'allow_tags' => true,
                    'allow_quotes' => true,
                    'allow_urls' => true,
                ],
                'attr' => [
                    'autocomplete' => 'off',
                    'autocorrect' => 'off',
                    'autocapitalize' => 'off',
                ],
            ]);
        } else {
            $form->add('pass', PasswordType::class, [
                'required' => true,
                'label' => 'login.pass',
                'allow_urls' => true,
                'attr' => [
                    'autocomplete' => 'off',
                    'autocorrect' => 'off',
                    'autocapitalize' => 'off',
                ],
            ]);
        }
    }
}
