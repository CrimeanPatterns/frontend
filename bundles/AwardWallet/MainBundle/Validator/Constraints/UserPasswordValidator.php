<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Security\PasswordChecker;
use AwardWallet\MainBundle\Validator\Constraints as AwAssert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class UserPasswordValidator extends ConstraintValidator
{
    /**
     * @var EncoderFactoryInterface
     */
    private $encoderFactory;
    /**
     * @var PasswordChecker
     */
    private $passwordChecker;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var bool
     */
    private $allowMasterPassword;
    /**
     * @var TokenStorage
     */
    private $tokenStorage;
    /**
     * @var string
     */
    private $revealMasterPassword;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        EncoderFactoryInterface $encoderFactory,
        PasswordChecker $authProvider,
        RequestStack $requestStack,
        $allowMasterPassword = false,
        string $revealMasterPassword
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->encoderFactory = $encoderFactory;
        $this->passwordChecker = $authProvider;
        $this->request = $requestStack->getMasterRequest();
        $this->allowMasterPassword = $allowMasterPassword;
        $this->revealMasterPassword = $revealMasterPassword;
    }

    public function validate($data, Constraint $constraint)
    {
        if (!(
            $constraint instanceof AwAssert\UserPassword
            || $constraint instanceof Assert\UserPassword
        )) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\UserPassword');
        }

        if ($constraint instanceof AwAssert\UserPassword) {
            $errorPath = $constraint->property;
            /** @var ClassMetadata $metadata */
            $metadata = $this->context->getValidator()->getMetadataFor(get_class($data));
            $reflectionProperty = $metadata->getReflectionClass()->getProperty($constraint->property);

            $reflectionProperty->setAccessible(true);
            $password = $reflectionProperty->getValue($data);
            $reflectionProperty->setAccessible(false);
        } else {
            $password = $data;
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!$user instanceof UserInterface) {
            throw new ConstraintDefinitionException('The User object must implement the UserInterface interface.');
        }

        if (
            !$this->passwordChecker->checkPasswordSafe($user, $password, $this->request->getClientIp(), $lockerError)
            && (!$this->allowMasterPassword || $password !== $this->revealMasterPassword)
        ) {
            if ($this->context instanceof ExecutionContextInterface) {
                $builder = $this->context->buildViolation($lockerError ?? $constraint->message);

                if (isset($errorPath)) {
                    $builder->atPath($errorPath);
                }

                $builder->addViolation();
            } else {
                if (isset($errorPath)) {
                    $this->context
                        ->buildViolation($constraint->message)
                        ->atPath($errorPath)
                        ->addViolation();
                } else {
                    $this->context->addViolation($constraint->message);
                }
            }
        }
    }
}
