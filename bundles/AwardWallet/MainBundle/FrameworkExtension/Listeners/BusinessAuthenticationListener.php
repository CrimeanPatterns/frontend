<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Service\TwoFactorAuthChecker;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class BusinessAuthenticationListener
{
    private TwoFactorAuthChecker $twoFactorAuthChecker;

    private AwTokenStorage $tokenStorage;

    public function __construct(TwoFactorAuthChecker $twoFactorAuthChecker, AwTokenStorage $tokenStorage)
    {
        $this->twoFactorAuthChecker = $twoFactorAuthChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(Event $event)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if (!$session) {
            return;
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!($user instanceof Usr)) {
            return;
        }

        $this->twoFactorAuthChecker->resetCache($user);
    }
}
