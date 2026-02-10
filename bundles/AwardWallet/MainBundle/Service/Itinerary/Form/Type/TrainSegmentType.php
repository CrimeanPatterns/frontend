<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\FormTypeHelper;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\TrainSegmentModel;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class TrainSegmentType extends AbstractSegmentItineraryType
{
    private TranslatorInterface $translator;

    public function __construct(FormTypeHelper $helper, TranslatorInterface $translator)
    {
        parent::__construct($helper);
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('carrier', TextType::class, [
                'label' => 'itineraries.trip.carrier',
            ])
            ->add('route', TextType::class, [
                'label' => 'itineraries.trip.route',
                'required' => false,
            ])
            ->add('departureStationCode', TextType::class, [
                'label' => 'itineraries.trip.dep-station-code',
                'required' => false,
            ])
            ->add('departureStation', GoogleAddressAutocompleteType::class, [
                'label' => 'itineraries.trip.dep-station',
                'attr' => [
                    'skip_js' => true,
                    'notice' => $this->translator->trans('general-address-notice', [], 'trips'),
                ],
            ]);

        $this->helper->addDepartureDate($builder);

        $builder
            ->add('arrivalStationCode', TextType::class, [
                'label' => 'itineraries.trip.arr-station-code',
                'required' => false,
            ])
            ->add('arrivalStation', GoogleAddressAutocompleteType::class, [
                'label' => 'itineraries.trip.arr-station',
                'attr' => [
                    'skip_js' => true,
                    'notice' => $this->translator->trans('general-address-notice', [], 'trips'),
                ],
            ]);

        $this->helper->addArrivalDate($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TrainSegmentModel::class,
            'translation_domain' => 'trips',
        ]);
    }

    public function getParent()
    {
        return TripsegmentType::class;
    }
}
