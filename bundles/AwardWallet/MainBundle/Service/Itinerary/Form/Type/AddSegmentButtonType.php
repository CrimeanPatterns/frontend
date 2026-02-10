<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ButtonTypeInterface;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddSegmentButtonType extends AbstractType implements ButtonTypeInterface
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
        ]);
    }

    /**
     * @deprecated
     */
    public function getBlockPrefix()
    {
        return 'add_segment_button';
    }

    public function getParent()
    {
        return ButtonType::class;
    }
}
