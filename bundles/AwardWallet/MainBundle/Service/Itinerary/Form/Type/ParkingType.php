<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\Model\ParkingModel;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParkingType extends AbstractItineraryType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->helper->addOwner($builder);
        $this->helper->addConfirmationNumber($builder, true);

        $builder
            ->add('parkingCompanyName', GooglePlacesAutocompleteType::class, [
                'label' => 'parking-company',
            ])
            ->add('address', GoogleAddressAutocompleteType::class, [
                'label' => 'address',
            ])
            ->add('startDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'label' => 'itineraries.parking.start-date',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ])
            ->add('endDate', DateTimeType::class, [
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'label' => 'itineraries.parking.end-date',
                'invalid_message' => 'invalid_date_and_time',
                'html5' => false,
            ])
            ->add('plate', TextType::class, [
                'label' => 'license-plate',
                'required' => false,
            ])
            ->add('spot', TextType::class, [
                'label' => 'parking-spot-number',
                'required' => false,
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
            'data_class' => ParkingModel::class,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'trips',
        ]);
    }

    public function getName()
    {
        return 'parking';
    }
}
