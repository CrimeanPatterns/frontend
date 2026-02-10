<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\Model\TaxiModel;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxiType extends AbstractItineraryType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->helper->addOwner($builder);
        $this->helper->addConfirmationNumber($builder, false);

        $builder
            ->add('taxiCompany', GooglePlacesAutocompleteType::class, [
                'label' => 'itineraries.taxi_ride.company',
            ])
            ->add('pickUpAddress', GoogleAddressAutocompleteType::class, [
                'label' => 'itineraries.rental.pickup-location',
            ])
            ->add('pickUpDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'label' => 'itineraries.rental.pickup-datetime',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ])
            ->add('dropOffAddress', GoogleAddressAutocompleteType::class, [
                'label' => 'itineraries.rental.dropoff-location',
            ])
            ->add('dropOffDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'label' => 'itineraries.rental.dropoff-datetime',
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
            'data_class' => TaxiModel::class,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'trips',
        ]);
    }
}
