<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\FormTypeHelper;
use AwardWallet\MainBundle\Service\Itinerary\Form\Model\FerrySegmentModel;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class FerrySegmentType extends AbstractSegmentItineraryType
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
            ->add('ferryCompany', TextType::class, [
                'label' => 'itineraries.trip.ferry.airline-name',
            ])
            ->add('route', TextType::class, [
                'label' => 'itineraries.trip.route',
                'required' => false,
            ])
            ->add('departurePort', GoogleAddressAutocompleteType::class, [
                'label' => 'departure-port',
                'attr' => [
                    'skip_js' => true,
                    'notice' => $this->translator->trans('general-address-notice', [], 'trips'),
                ],
            ]);

        $this->helper->addDepartureDate($builder);

        $builder
            ->add('arrivalPort', GoogleAddressAutocompleteType::class, [
                'label' => 'arrival-port',
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
            'data_class' => FerrySegmentModel::class,
            'translation_domain' => 'trips',
        ]);
    }

    public function getParent()
    {
        return TripsegmentType::class;
    }
}
