<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Globals\StringHandler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ConditionOldValidator extends ConstraintValidator
{
    /**
     * @param ConditionOld $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (
            is_callable($callable = [$value, $constraint->if])
            || is_callable($callable = [get_class($value), $constraint->if])
            || is_callable($callable = $constraint->if)
            || (
                $constraint->onObject
                && is_object($object = $this->context->getObject())
                && (
                    is_callable($callable = [$object, $constraint->if])
                    || is_callable($callable = [\get_class($object), $constraint->if])
                )
            )
        ) {
            $condition = $callable($value);
        } else {
            if (
                !is_string($constraint->if)
                || !is_object($value)
                || StringHandler::isEmpty($constraint->if)
                || !method_exists($value, $constraint->if)
            ) {
                throw new \RuntimeException("Callable or method \"{$constraint->if}\" was not found");
            }

            $condition = $value->{$constraint->if}();
        }

        if ($constraints = $condition ? $constraint->then : $constraint->else) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($value, $constraints);
        }
    }
}
