<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\Model\ReservationModel;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReservationType extends AbstractItineraryType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->helper->addOwner($builder);
        $this->helper->addConfirmationNumber($builder, true);

        $builder
            ->add('hotelName', GooglePlacesAutocompleteType::class, [
                'label' => 'itineraries.reservation.hotel-name',
                'allow_quotes' => true,
            ])
            ->add('address', GoogleAddressAutocompleteType::class, [
                'label' => 'itineraries.address',
                'allow_quotes' => true,
                'attr' => [
                    'class' => 'inline-input',
                ],
            ])
            ->add('checkInDate', DateTimeType::class, [
                'label' => 'itineraries.reservation.check-in-date',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ])
            ->add('checkOutDate', DateTimeType::class, [
                'label' => 'itineraries.reservation.check-out-date',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'itineraries.phone',
                'required' => false,
            ]);

        $this->helper->addNotes($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ReservationModel::class,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'trips',
        ]);
    }

    /**
     * @deprecated
     */
    public function getBlockPrefix()
    {
        return 'reservation';
    }
}
