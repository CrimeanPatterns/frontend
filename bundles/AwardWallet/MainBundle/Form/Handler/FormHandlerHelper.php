<?php

namespace AwardWallet\MainBundle\Form\Handler;

use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class FormHandlerHelper
{
    /**
     * @var AuthorizationChecker
     */
    private $authorizationChecker;
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    public function __construct(
        AuthorizationChecker $authorizationChecker,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->propertyAccessor = $propertyAccessor;
    }

    public function throwIfImpersonated()
    {
        if ($this->authorizationChecker->isGranted('USER_IMPERSONATED')) {
            throw new ImpersonatedException();
        }
    }

    public function throwIfCsrfIsInvalid()
    {
        if (!$this->authorizationChecker->isGranted('CSRF')) {
            throw new AccessDeniedHttpException('Invalid CSRF-token');
        }
    }

    public function throwIfNotIdempotent()
    {
        $this->throwIfImpersonated();
        $this->throwIfCsrfIsInvalid();
    }

    public function isSubmitted(FormInterface $form, Request $request)
    {
        return $form->getConfig()->getMethod() === $request->getMethod();
    }

    public function isSynchronizedDeep(FormInterface $form)
    {
        if (!$form->isSynchronized()) {
            return false;
        }

        foreach ($form as $child) {
            $childSynchronized = $this->isSynchronizedDeep($child);

            if (!$childSynchronized) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param object|array $source
     * @param object|array $destination
     * @param string[] $properties
     * @return array|object
     */
    public function copyProperties($source, $destination, array $properties)
    {
        foreach ($properties as $property) {
            if (!$this->propertyAccessor->isReadable($destination, $property)) {
                continue;
            }

            $this->propertyAccessor->setValue(
                $destination,
                $property,
                $this->propertyAccessor->getValue($source, $property)
            );
        }

        return $destination;
    }
}
