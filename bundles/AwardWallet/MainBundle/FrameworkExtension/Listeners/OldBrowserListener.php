<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\FrameworkExtension\Exceptions\OldBrowserException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class OldBrowserListener.
 *
 * @deprecated
 *
 * todo remove on public new site
 */
class OldBrowserListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ($event->getRequest()->headers->has('user-agent')) {
            $controller = $event->getRequest()->attributes->get('_controller');

            if (strpos($controller, '::') !== false) {
                [$controller] = explode('::', $controller);
            }

            if ($controller == 'AwardWallet\\MainBundle\\Controller\\BookingController') {
                $ua = $event->getRequest()->headers->get('user-agent');

                if (preg_match('/MSIE (\d+)\./', $ua, $m)) {
                    $ie_version = (int) $m[1];

                    if ($ie_version <= 7) {
                        throw new OldBrowserException();
                    }
                }
            }
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof OldBrowserException) {
            $event->stopPropagation();
            $response = new RedirectResponse('/old-browser.html');
            $event->setResponse($response);
        }
    }
}
