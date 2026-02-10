<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\Model\TripModel;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FlightType extends AbstractItineraryType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->helper->addOwner($builder);
        $this->helper->addConfirmationNumber($builder, true);

        $builder
            ->add('segments', FlightSegmentCollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => [
                    'label' => 'flight.segment',
                    'translation_domain' => 'trips',
                ],
            ])
            ->add('addSegment', AddSegmentButtonType::class, [
                'label' => 'itineraries.trip.add-segment',
                'translation_domain' => 'trips',
            ]);

        $this->helper->addNotes($builder);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TripModel::class,
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
        return 'flight';
    }
}
