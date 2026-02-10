<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Account extends Constraint
{
    public function validatedBy()
    {
        return 'account';
    }

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
