<?php

namespace AwardWallet\MainBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbPhoneNumberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('Provider', TextType::class, [
                'required' => true,
                'label' => 'booking.forms.seat_assignments.provider',
                'attr' => ['class' => 'cp-autocomplete', 'data-param' => 'seats', 'maxlength' => 255],
            ])
            ->add('Phone', TextType::class, [
                'required' => true,
                'label' => 'booking.forms.seat_assignments.phone',
                'attr' => ['maxlength' => 255],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AwardWallet\\MainBundle\\Entity\\AbPhoneNumber',
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'booking',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'booking_request_phone_number';
    }
}
