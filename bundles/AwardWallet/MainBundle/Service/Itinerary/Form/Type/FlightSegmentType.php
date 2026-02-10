<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\Model\FlightSegmentModel;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlightSegmentType extends AbstractSegmentItineraryType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('airlineName', AirlineType::class, [
                'label' => 'itineraries.trip.air.airline-name',
            ])
            ->add('flightNumber', TextType::class, [
                'label' => 'itineraries.trip.air.flight-number',
            ])
            ->add('departureAirport', AircodeType::class, [
                'label' => 'itineraries.trip.dep-code',
            ]);

        $this->helper->addDepartureDate($builder);

        $builder
            ->add('arrivalAirport', AircodeType::class, [
                'label' => 'itineraries.trip.arr-code',
            ]);

        $this->helper->addArrivalDate($builder);

        $builder
            ->add('retrieve', ButtonType::class, [
                'label' => 'itineraries.trip.auto_complete',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FlightSegmentModel::class,
            'translation_domain' => 'trips',
        ]);
    }

    public function getParent()
    {
        return TripsegmentType::class;
    }
}
