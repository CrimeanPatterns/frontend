<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class PhonegapListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->getMethod() == 'OPTIONS') {
            return;
        } else {
            // missing REQUEST_URI in unit tests
            if (!isset($_SERVER['REQUEST_URI'])) {
                $_SERVER['REQUEST_URI'] = $request->getRequestUri();
            }
        }
    }
}
