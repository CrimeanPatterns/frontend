<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use Psr\Log\LoggerInterface;

class ScriptedStep extends AbstractStep
{
    public const ID = 'scripted';

    public const WHITELISTED_IP_SERVER_PARAMETER_NAME = "whiteListedIp";

    public const SESSION_VALUE_NAME = 'client_check';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function supports(Credentials $credentials): bool
    {
        if (!empty($credentials->getRequest()->server->get(self::WHITELISTED_IP_SERVER_PARAMETER_NAME))) {
            $this->logger->info('Server has white-listed IP, skip Scripted check', $this->getLogContext($credentials));

            return false;
        }

        $this->logger->info('Scripted check required', $this->getLogContext($credentials));

        return true;
    }

    protected function doCheck(Credentials $credentials): void
    {
        $request = $credentials->getRequest();

        $session = $request->getSession();
        $clientCheck = $session->get(self::SESSION_VALUE_NAME, null);
        $clientResult = $credentials->getStepData()->getScripted();

        if (!(!empty($clientCheck) && !empty($clientResult) && \hash_equals((string) $clientCheck['result'], (string) $clientResult))) {
            $this->logger->warning("Scripted check failed", $this->getLogContext($credentials, ["task" => $clientCheck, "response" => $clientResult]));
            $this->throwErrorException("Bad credentials");
        }

        $this->logger->info('Scripted check succeeded', $this->getLogContext($credentials));
    }
}
