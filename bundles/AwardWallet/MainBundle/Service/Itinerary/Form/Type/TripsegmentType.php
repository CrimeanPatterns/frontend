<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use AwardWallet\MainBundle\Service\Itinerary\Form\Model\AbstractSegmentModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripsegmentType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AbstractSegmentModel::class,
        ]);
    }

    /**
     * @deprecated
     */
    public function getBlockPrefix()
    {
        return 'trip_segment';
    }
}
