<?php

namespace AwardWallet\MainBundle\Security;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class SwitchUserExitListener
{
    /**
     * @var AuthenticationListener
     */
    private $authenticationListener;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(AuthenticationListener $authenticationListener, TokenStorageInterface $tokenStorage)
    {
        $this->authenticationListener = $authenticationListener;
        $this->tokenStorage = $tokenStorage;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        // out of the box, symfony will not send SwitchUserEvent
        // when we are doing ?_switch_user=_exit to anonymous
        if (
            strpos($request->getRequestUri(), '_switch_user=_exit') !== false
            && !$request->attributes->has(AuthenticationListener::SWITCH_USER_FIRED)
            && is_string($this->tokenStorage->getToken()->getUser())
        ) {
            $this->authenticationListener->onSwitchUser(new SwitchUserEvent($request, new Usr()));
        }
    }
}
