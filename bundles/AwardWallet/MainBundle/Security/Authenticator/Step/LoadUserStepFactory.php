<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use Psr\Log\LoggerInterface;

class LoadUserStepFactory implements StepFactoryInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function make(...$args): StepInterface
    {
        return new LoadUserStep($this->logger, $args[0]);
    }

    public function getId(): string
    {
        return LoadUserStep::ID;
    }
}
