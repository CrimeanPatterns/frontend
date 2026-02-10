<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\Exception;

use AwardWallet\MainBundle\Security\Authenticator\Step\StepInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

abstract class AbstractStepAuthenticationException extends AuthenticationException implements StepAuthenticationExceptionInterface
{
    /**
     * @var StepInterface
     */
    protected $step;

    protected $data;

    /**
     * StepAwareAuthenticationException constructor.
     *
     * @param $data mixed
     */
    public function __construct(StepInterface $step, $data, string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->step = $step;
        $this->data = $data;
    }

    public function getStep(): StepInterface
    {
        return $this->step;
    }

    public function getData()
    {
        return $this->data;
    }
}
