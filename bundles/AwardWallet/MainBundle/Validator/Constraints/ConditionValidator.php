<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Globals\PropertyAccess\SafeCallPropertyAccessor;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ConditionValidator extends ConstraintValidator
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;
    /**
     * @var SafeCallPropertyAccessor
     */
    private $propertyAccessor;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->propertyAccessor = new SafeCallPropertyAccessor();
        $this->expressionLanguage->addFunction(
            new ExpressionFunction(
                'property_path',
                function ($object, $propertyPath) {
                    return \sprintf('$property_accessor->getValue(%s, %s)', $object, $propertyPath);
                },
                function (array $variables, $object, $propertyPath) {
                    return $this->propertyAccessor->getValue($object, $propertyPath);
                }
            )
        );
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Condition) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Condition');
        }

        $variables = [];
        $variables['value'] = $value;
        $variables['this'] = $this->context->getObject();
        $variables['property_accessor'] = $this->propertyAccessor;

        $nextConstraints = $this->expressionLanguage->evaluate($constraint->if, $variables) ?
            $constraint->then :
            $constraint->else;

        if ($nextConstraints) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($value, $nextConstraints);
        }
    }
}
