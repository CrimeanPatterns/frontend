<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ByteLengthValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ByteLength) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\ByteLength');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $stringValue = (string) $value;
        $length = strlen($stringValue);

        if ($constraint->min == $constraint->max && $length != $constraint->min) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->exactMessage)
                    ->setParameter('{{ value }}', $this->formatValue($stringValue))
                    ->setParameter('{{ limit }}', $constraint->min)
                    ->setInvalidValue($value)
                    ->setPlural((int) $constraint->min)
                    ->addViolation();
            } else {
                throw new \RuntimeException('Context must implement ' . ExecutionContextInterface::class);
            }

            return;
        }

        if (null !== $constraint->max && $length > $constraint->max) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->maxMessage)
                    ->setParameter('{{ value }}', $this->formatValue($stringValue))
                    ->setParameter('{{ limit }}', $constraint->max)
                    ->setInvalidValue($value)
                    ->setPlural((int) $constraint->max)
                    ->addViolation();
            } else {
                throw new \RuntimeException('Context must implement ' . ExecutionContextInterface::class);
            }

            return;
        }

        if (null !== $constraint->min && $length < $constraint->min) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->minMessage)
                    ->setParameter('{{ value }}', $this->formatValue($stringValue))
                    ->setParameter('{{ limit }}', $constraint->min)
                    ->setInvalidValue($value)
                    ->setPlural((int) $constraint->min)
                    ->addViolation();
            } else {
                throw new \RuntimeException('Context must implement ' . ExecutionContextInterface::class);
            }
        }
    }
}
