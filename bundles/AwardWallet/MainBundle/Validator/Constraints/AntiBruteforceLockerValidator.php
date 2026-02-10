<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AntiBruteforceLockerValidator extends ConstraintValidator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param AntiBruteforceLocker $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $locker = $constraint->service;

        if (is_string($locker)) {
            $locker = $this->container->get($locker);
        }

        if (!is_object($locker) || !($locker instanceof AntiBruteforceLockerService)) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\AntiBrueforceLocker');
        }

        if (is_callable($constraint->keyMethod)) {
            $key = call_user_func($constraint->keyMethod, $value);
        } else {
            if (
                !is_string($constraint->keyMethod)
                || !is_object($value)
                || StringHandler::isEmpty($constraint->keyMethod)
                || !method_exists($value, $constraint->keyMethod)
            ) {
                throw new \RuntimeException("Method \"{$constraint->keyMethod}\" was not found");
            }

            $key = $value->{$constraint->keyMethod}();
        }

        if (false === $key || StringHandler::isEmpty($key)) {
            throw new \UnexpectedValueException('Locker key should not be empty');
        }

        $error = $locker->checkForLockout($key);

        if (isset($error)) {
            $violation = $this->context->buildViolation($error);

            if (!StringHandler::isEmpty($constraint->field)) {
                $violation->atPath($constraint->field);
            }

            $violation->addViolation();
        }
    }
}
