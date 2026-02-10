<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION", "PROPERTY"})
 */
class Condition extends Constraint
{
    /**
     * Expression.
     *
     * @var string
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
        return 'condition';
    }
}
