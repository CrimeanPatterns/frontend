<?php

namespace AwardWallet\MainBundle\Form\Type;

use AwardWallet\MainBundle\Entity\Contactus;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactUsUnauthType extends ContactUsAuthType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('fullname', TextType::class, [
            'label' => /** @Desc("Full Name") */ 'contactus.full-name',
            'error_bubbling' => false,
            'allow_urls' => true,
            'attr' => ['class' => 'inputTxt'],
        ]);
        $builder->add('email', TextType::class, [
            'label' => /** @Desc("Email") */ 'contactus.email',
            'error_bubbling' => false,
            'allow_urls' => true,
            'attr' => ['class' => 'inputTxt'],
        ]);
        $builder->add('phone', TextType::class, [
            'label' => /** @Desc("Phone") */ 'contactus.phone',
            'required' => false,
            'error_bubbling' => false,
            'allow_urls' => true,
            'attr' => ['class' => 'inputTxt'],
        ]);

        if ($options['with_captcha']) {
            $builder->add('captcha', CaptchaType::class, [
                'label' => /** @Desc("Security Number") */ 'contactus.captcha.lable',
                'error_bubbling' => false,
                'attr' => ['notice' => 'Input the number that you see on the image above', 'class' => 'inputTxt'],
                'allow_urls' => true,
                'allow_quotes' => true,
                'allow_tags' => true,
            ]);
        }
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(['with_captcha']);
        $resolver->setDefaults([
            'validation_groups' => ['unauth'],
            'translation_domain' => 'contactus',
            'data_class' => Contactus::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'contact_us_unauth';
    }
}
