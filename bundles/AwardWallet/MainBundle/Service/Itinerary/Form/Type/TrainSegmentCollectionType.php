<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TrainSegmentCollectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => TrainSegmentType::class,
        ]);
    }

    public function getParent()
    {
        return SegmentCollectionType::class;
    }
}
