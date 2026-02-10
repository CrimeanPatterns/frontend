<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ReauthenticatorWrapper
{
    /**
     * @var Reauthenticator
     */
    private $reauthenticator;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(
        Reauthenticator $reauthenticator,
        TokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack
    ) {
        $this->reauthenticator = $reauthenticator;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->requestStack = $requestStack;
    }

    public function start(): ReauthResponse
    {
        $authUser = $this->getAuthUser();
        $action = (string) $this->getFromRequest('action');
        $environment = $this->getEnvironment();
        $this->checkAction($action);

        return $this->reauthenticator->start(
            $authUser,
            $action,
            $environment
        );
    }

    public function verify(): ResultResponse
    {
        $authUser = $this->getAuthUser();
        $reauthRequest = $this->getReauthRequest();
        $environment = $this->getEnvironment();
        $this->checkAction($reauthRequest->getAction());

        return $this->reauthenticator->verify(
            $authUser,
            $reauthRequest,
            $environment
        );
    }

    public function isReauthenticated(string $action): bool
    {
        $environment = $this->getEnvironment();

        return $this->reauthenticator->isReauthenticated($action, $environment->getIp());
    }

    public function reset(string $action)
    {
        $this->reauthenticator->reset($action);
    }

    protected function checkAction(string $action)
    {
        if (!Action::validateAction($action)) {
            throw new \InvalidArgumentException(sprintf('Unknown "%s" action', $action));
        }
    }

    private function getAuthUser(): AuthenticatedUser
    {
        $token = $this->tokenStorage->getToken();

        if (!isset($token) || !($user = $token->getUser()) instanceof Usr) {
            throw new \RuntimeException('User is not authenticated');
        }

        /** @var Usr $user */
        return new AuthenticatedUser($user, $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA'));
    }

    private function getEnvironment(): Environment
    {
        return Environment::fromRequest($this->requestStack->getMasterRequest());
    }

    private function getReauthRequest(): ReauthRequest
    {
        return new ReauthRequest(
            $this->getFromRequest('action'),
            $this->getFromRequest('context'),
            $this->getFromRequest('input'),
            $this->getFromRequest('intent')
        );
    }

    private function getFromRequest(string $key): ?string
    {
        $request = $this->requestStack->getMasterRequest();
        $value = $request->request->get($key, null);

        if (!is_string($value) && !is_null($value)) {
            return null;
        }

        return $value;
    }
}
