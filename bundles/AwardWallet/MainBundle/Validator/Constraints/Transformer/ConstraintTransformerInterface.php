<?php

namespace AwardWallet\MainBundle\Validator\Constraints\Transformer;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadata;

interface ConstraintTransformerInterface
{
    /**
     * @return string
     */
    public function getSupportedConstraint();

    /**
     * @return Constraint
     */
    public function transform(Constraint $constraint, ClassMetadata $classMetadata);
}
