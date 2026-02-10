<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Globals\PropertyAccess\SafeCallPropertyAccessor;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ConversionValidator extends ConstraintValidator
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

        $this->expressionLanguage->addFunction(
            new ExpressionFunction(
                'call',
                function ($callable, ...$params) {
                    return "\\call_user_func({$callable}, " . \implode(', ', $params) . ")";
                },
                function (array $variables, $callable, ...$params) {
                    return $callable(...$params);
                }
            )
        );
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Conversion) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\Conversion');
        }

        $value = $this->expressionLanguage->evaluate($constraint->expression, [
            'value' => $value,
            'this' => $this->context->getObject(),
            'property_accessor' => $this->propertyAccessor,
        ]);

        if ($constraint->constraints) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate($value, $constraint->constraints);
        }
    }
}
