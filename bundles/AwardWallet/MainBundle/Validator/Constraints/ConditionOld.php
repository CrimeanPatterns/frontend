<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class Condition.
 *
 * @Annotation
 * @Target({"CLASS", "ANNOTATION", "PROPERTY"})
 * @deprecated use \AwardWallet\MainBundle\Validator\Constraints\Condition instead
 */
class ConditionOld extends Constraint
{
    /**
     * @var callable|string
     */
    public $if;
    /**
     * @var Constraint[]
     */
    public $then = [];
    /**
     * @var Constraint[]
     */
    public $else = [];

    public function getTargets()
    {
        return [parent::CLASS_CONSTRAINT, parent::PROPERTY_CONSTRAINT];
    }

    public function validatedBy()
    {
        return 'condition_old';
    }
}
