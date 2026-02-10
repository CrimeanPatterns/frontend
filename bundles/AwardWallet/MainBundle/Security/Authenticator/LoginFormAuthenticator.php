<?php

namespace AwardWallet\MainBundle\Security\Authenticator;

use AwardWallet\MainBundle\Security\Authenticator\EntryPoint\FormEntryPointHelper;
use AwardWallet\MainBundle\Security\Authenticator\EntryPoint\LoginEntryPointInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Contracts\Translation\TranslatorInterface;

class LoginFormAuthenticator extends AbstractGuardAuthenticator
{
    protected const REQUEST_ATTRIBUTE_ENTRY_POINT_ID = '_login_form_authenticator_entry_point_id';

    /**
     * @var LoginEntryPointInterface[]
     */
    protected $entryPoints;
    /**
     * @var LoginEntryPointInterface[]
     */
    protected $entryPointsById = [];
    /**
     * @var FormEntryPointHelper
     */
    protected $formEntryPointHelper;
    /**
     * @var RequestStack
     */
    protected $requestStack;
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        iterable $entryPoints,
        FormEntryPointHelper $formEntryPointHelper,
        RequestStack $requestStack,
        TranslatorInterface $translator
    ) {
        $this->entryPoints = $entryPoints;
        $this->formEntryPointHelper = $formEntryPointHelper;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    public function supports(Request $request)
    {
        $entryPoint = $this->findEntryPoint($request);

        if ($entryPoint) {
            return $entryPoint->supportsLogin($request);
        }

        $this->throwError();
    }

    public function start(Request $request, ?AuthenticationException $authException = null)
    {
        $entryPoint = $this->findEntryPoint($request);

        if ($entryPoint) {
            return $entryPoint->start($request, $authException);
        }

        $this->throwError();
    }

    public function getCredentials(Request $request): Credentials
    {
        $entryPoint = $this->findEntryPoint($request);

        if ($entryPoint) {
            return $entryPoint->getCredentials($request);
        }

        $this->throwError();
    }

    /**
     * @param Credentials $credentials
     * @return UserInterface|null
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $entryPoint = $this->findEntryPoint($credentials->getRequest());

        if ($entryPoint) {
            return $entryPoint->loadUserSteps($credentials, $userProvider);
        }

        $this->throwError();
    }

    /**
     * @param Credentials $credentials
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $entryPoint = $this->findEntryPoint($credentials->getRequest());

        if ($entryPoint) {
            return $entryPoint->postLoadUserSteps($credentials, $user);
        }

        $this->throwError();
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $entryPoint = $this->findEntryPoint($request);

        if ($entryPoint) {
            return $entryPoint->onAuthenticationFailure($request, $exception);
        }

        return new JsonResponse(['error' => $this->translator->trans('error.auth.failure')], 403);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $entryPoint = $this->findEntryPoint($request);

        if ($entryPoint) {
            return $entryPoint->onAuthenticationSuccess($request, $token, $providerKey);
        }

        return $this->formEntryPointHelper->onAuthenticationSuccess($request, $token, $providerKey);
    }

    /**
     * @return bool
     */
    public function supportsRememberMe()
    {
        $request = $this->requestStack->getMasterRequest();

        if ($request) {
            $entryPoint = $this->findEntryPoint($request);

            if ($entryPoint) {
                return $entryPoint->supportsRememberMe($request);
            }
        }

        return false;
    }

    protected function findEntryPoint(Request $request): ?LoginEntryPointInterface
    {
        if ($request->attributes->has(self::REQUEST_ATTRIBUTE_ENTRY_POINT_ID)) {
            $entryPointId = $request->attributes->get(self::REQUEST_ATTRIBUTE_ENTRY_POINT_ID);

            if ($entryPointId === null) {
                return null;
            }

            if (array_key_exists($entryPointId, $this->entryPointsById)) {
                return $this->entryPointsById[$entryPointId];
            }
        }

        foreach ($this->entryPoints as $entryPoint) {
            if ($entryPoint->supportsArea($request)) {
                $request->attributes->set(self::REQUEST_ATTRIBUTE_ENTRY_POINT_ID, $entryPoint->getId());
                $this->entryPointsById[$entryPoint->getId()] = $entryPoint;

                return $entryPoint;
            }
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE_ENTRY_POINT_ID, null);

        return null;
    }

    /**
     * @throws UsernameNotFoundException
     */
    protected function throwError(): void
    {
        throw new UsernameNotFoundException($this->translator->trans('Bad credentials', [], 'validators'));
    }
}
