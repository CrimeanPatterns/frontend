<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ContentLengthListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();

        if ($response->headers->has('Content-Length')) {
            return;
        }

        if (stripos($response->headers->get('Content-Type'), 'text/html') === false) {
            return;
        }

        // Content-Length required for cloudfront to enable compression
        $response->headers->set('Content-Length', \strlen($response->getContent()));
    }
}
