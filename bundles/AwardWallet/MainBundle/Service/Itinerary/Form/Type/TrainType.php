<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\Model\TripModel;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainType extends AbstractItineraryType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->helper->addOwner($builder);
        $this->helper->addConfirmationNumber($builder, true);

        $builder
            ->add('segments', TrainSegmentCollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => [
                    'label' => 'train.segment',
                    'translation_domain' => 'trips',
                ],
            ])
            ->add('addSegment', AddSegmentButtonType::class, [
                'label' => 'itineraries.train.add-segment',
                'translation_domain' => 'trips',
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
            'data_class' => TripModel::class,
            /** @Ignore */
            'label' => false,
            'translation_domain' => 'trips',
        ]);
    }
}
