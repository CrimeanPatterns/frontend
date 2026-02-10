<?php

namespace AwardWallet\MainBundle\Validator\Constraints\Transformer;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class TransformerManager
{
    /**
     * @var ConstraintTransformerInterface[]
     */
    private $transformers = [];

    public function addConstraintTransformer(ConstraintTransformerInterface $constraintTransformer)
    {
        $this->transformers[$constraintTransformer->getSupportedConstraint()] = $constraintTransformer;
    }

    /**
     * @return Constraint|null
     */
    public function transform(Constraint $constraint, ClassMetadata $classMetadata)
    {
        $class = get_class($constraint);

        return isset($this->transformers[$class]) ?
            $this->transformers[$class]->transform($constraint, $classMetadata) :
            $constraint;
    }
}
