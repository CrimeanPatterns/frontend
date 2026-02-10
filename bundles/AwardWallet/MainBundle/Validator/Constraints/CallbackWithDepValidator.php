<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CallbackWithDepValidator extends ConstraintValidator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function validate($object, Constraint $constraint)
    {
        if (!$constraint instanceof CallbackWithDep) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\CallbackWithDep');
        }

        $cb = $constraint->callback;

        if (is_array($cb) || $cb instanceof \Closure) {
            if (!is_callable($cb)) {
                throw new ConstraintDefinitionException(sprintf('"%s::%s" targeted by CallbackWithDep constraint is not a valid callable', $cb[0], $cb[1]));
            }

            call_user_func_array($cb, $this->getArgs($constraint, $object, $this->context));

            return;
        }

        if (null === $object) {
            return;
        }

        if (!method_exists($object, $cb)) {
            throw new ConstraintDefinitionException(sprintf('Method "%s" targeted by CallbackWithDep constraint does not exist', $cb));
        }
        $reflMethod = new \ReflectionMethod($object, $cb);

        if ($reflMethod->isStatic()) {
            $reflMethod->invokeArgs(null, $this->getArgs($constraint, $object, $this->context));
        } else {
            $reflMethod->invokeArgs($object, $this->getArgs($constraint, $this->context));
        }
    }

    private function getArgs(Constraint $constraint)
    {
        $arg_list = array_slice(func_get_args(), 1);
        $services = $constraint->services;

        if (empty($services)) {
            return $arg_list;
        }

        if (!is_array($services) && !is_string($services)) {
            throw new ConstraintDefinitionException('The option "services" must be an array or a string');
        }

        if (!is_array($services)) {
            $services = [$services];
        }

        foreach ($services as $service) {
            $arg_list[] = $this->container->get($service);
        }

        return $arg_list;
    }
}
