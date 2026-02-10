<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"ANNOTATION", "CLASS", "PROPERTY"})
 */
class AndX extends Constraint
{
    /**
     * @var Constraint[]
     */
    public $constraints = [];

    /**
     * Lazy constructor.
     *
     * @param Constraint[] $constraints
     */
    public function __construct($constraints = null)
    {
        parent::__construct($constraints);

        $this->constraints = $constraints['constraints'] ?? $constraints;
    }

    public function getDefaultOption()
    {
        return 'constraints';
    }

    public function validatedBy()
    {
        return 'andx';
    }

    public function getTargets()
    {
        return [parent::PROPERTY_CONSTRAINT, parent::CLASS_CONSTRAINT];
    }
}
