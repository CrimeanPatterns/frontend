<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ServiceValidator extends ConstraintValidator
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Service) {
            throw new UnexpectedTypeException($constraint, Service::class);
        }

        if (!$this->container->has($constraint->name)) {
            throw new ConstraintDefinitionException("Service \"{$constraint->name}\" was not found.");
        }

        $service = $this->container->get($constraint->name);

        if (isset($constraint->method)) {
            if (method_exists($service, $constraint->method)) {
                $callable = [$service, $constraint->method];
            } else {
                throw new ConstraintDefinitionException("Method \"{$constraint->method}\" in service \"{$constraint->name}\" was not found");
            }
        } else {
            if (is_callable($service)) {
                $callable = $service;
            } elseif (method_exists($service, 'validate')) {
                $callable = [$service, 'validate'];
            } else {
                throw new ConstraintDefinitionException(sprintf('Instance of "%s" is not callable', get_class($service)));
            }
        }

        $message = $callable($value, $this->context, $constraint->errorPath ?? null);

        if (null === $message) {
            return;
        }

        $violationBuilder = $this->context->buildViolation($message);

        if (isset($constraint->errorPath)) {
            $violationBuilder->atPath($constraint->errorPath);
        }

        $violationBuilder->addViolation();
    }
}
