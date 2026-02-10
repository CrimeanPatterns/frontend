<?php

namespace AwardWallet\MainBundle\Validator\Constraints\Transformer;

use AwardWallet\MainBundle\Validator\Constraints\UniqueEntity as Target;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity as Source;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class UniqueEntityTransformer implements ConstraintTransformerInterface
{
    public function getSupportedConstraint()
    {
        return Source::class;
    }

    /**
     * @param Source $constraint
     * @return Target|null
     */
    public function transform(Constraint $constraint, ClassMetadata $classMetadata)
    {
        $reflClass = $classMetadata->getReflectionClass();

        foreach ($constraint->fields as $field) {
            if (!$reflClass->hasProperty($field)) {
                return null;
            }
        }

        return new Target([
            'message' => $constraint->message,
            'em' => $constraint->em,
            'repositoryMethod' => $constraint->repositoryMethod,
            'fields' => $constraint->fields,
            'errorPath' => $constraint->errorPath,
            'ignoreNull' => $constraint->ignoreNull,
        ]);
    }
}
