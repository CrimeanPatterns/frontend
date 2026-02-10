<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DateSequence extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
