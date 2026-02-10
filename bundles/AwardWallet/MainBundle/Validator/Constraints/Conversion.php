<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS", "ANNOTATION", "PROPERTY"})
 */
class Conversion extends Constraint
{
    /**
     * Expression.
     *
     * @var string
     */
    public $expression;
    /**
     * Constraints.
     *
     * @var Constraint[]
     */
    public $constraints = [];

    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->expression = $options['expression'] ?? $options;
        $this->constraints = $options['constraints'] ?? $options;
    }

    public function validatedBy()
    {
        return 'conversion';
    }

    public function getTargets()
    {
        return [parent::CLASS_CONSTRAINT, parent::PROPERTY_CONSTRAINT];
    }
}
