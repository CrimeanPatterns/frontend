<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class JsonRequestTransformerListener implements EventSubscriberInterface
{
    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->attributes->get('_json_decode')) {
            return;
        }

        $this->transformJsonBody($request);
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    private function isJsonRequest(Request $request)
    {
        return 'json' === $request->getContentType();
    }

    private function transformJsonBody(Request $request)
    {
        if (!$this->isJsonRequest($request)) {
            return false;
        }

        json_encode(null);

        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if ($data === null) {
            return true;
        }

        if (!\is_array($data)) {
            return true;
        }

        $request->request->replace($data);

        return true;
    }
}
