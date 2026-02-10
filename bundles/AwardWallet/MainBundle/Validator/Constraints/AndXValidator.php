<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AndXValidator extends ConstraintValidator
{
    /**
     * @param AndX $lazyConstraint
     */
    public function validate($value, Constraint $lazyConstraint)
    {
        $violationsCount = count($this->context->getViolations());

        foreach ($lazyConstraint->constraints as $constraints) {
            if (!\is_array($constraints)) {
                $constraints = [$constraints];
            }

            foreach ($constraints as $constraint) {
                // TODO: implement proper group handling
                $constraint->groups = [Constraint::DEFAULT_GROUP];
                $this->context
                    ->getValidator()
                    ->inContext($this->context)
                    ->validate($value, $constraint);

                if (count($this->context->getViolations()) > $violationsCount) {
                    return;
                }
            }
        }
    }
}
