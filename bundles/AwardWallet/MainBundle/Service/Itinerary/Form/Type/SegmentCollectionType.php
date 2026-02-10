<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SegmentCollectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => TripsegmentType::class,
            'label' => false,
            'by_reference' => false,
        ]);
    }

    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * @deprecated
     */
    public function getBlockPrefix()
    {
        return 'segment_collection';
    }
}
