<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\FrameworkExtension\RequestAttributes;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CSRFListener
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;
    /**
     * @var bool
     */
    private $secureCookie;
    private $session;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager, $protocol, SessionInterface $session)
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->secureCookie = ($protocol === 'https');
        $this->session = $session;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        // do not interfere with Https listener - do not start session on http
        // we do not want session on http on prod, only redirect to https
        // otherwise session will be started by $this->csrfTokenManager->getToken('')
        if ($this->secureCookie && $event->getRequest()->getScheme() === 'http' && !$this->session->isStarted()) {
            return;
        }

        if (!$event->isMasterRequest() || RequestAttributes::isSessionLessRequest($event->getRequest())) {
            return;
        }

        $token = $this->csrfTokenManager->getToken('')->getValue();
        $headers = $event->getResponse()->headers;
        $headers->set('X-XSRF-TOKEN', $token);
        $requestMatcher = new RequestMatcher();
        // new mobile
        $requestMatcher->matchPath('^(/m/api|/mobile)');

        if (!$requestMatcher->matches($event->getRequest()) && $event->getRequest()->attributes->get("csrf_failed")) {
            $event->getResponse()
                ->setStatusCode(403)
                ->headers->set('X-XSRF-FAILED', 'true');
        }
    }
}
