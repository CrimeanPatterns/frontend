<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CardExpiration extends Constraint
{
    public function validatedBy()
    {
        return 'card_expiration';
    }

    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
