<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * this listener will fill $_GET, $POST, etc. from symfony Request, to test old TBaseForm-like code
 * used to test /manager/ pages.
 */
class CodeceptionFillGlobalsListener
{
    private const ATTR = 'aw_codecept_globals_set';

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (strpos($request->getPathInfo(), '/manager/') !== 0) {
            return;
        }

        if (count($_GET) > 0) {
            return;
        }

        $_GET = $request->query->all();
        $_POST = $request->request->all();
        $_SERVER['REQUEST_METHOD'] = $request->getMethod();
        $request->attributes->set(self::ATTR, true);
    }

    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        if ($event->getRequest()->attributes->has(self::ATTR)) {
            $_GET = [];
            $_POST = [];
            unset($_SERVER['REQUEST_METHOD']);
        }
    }
}
