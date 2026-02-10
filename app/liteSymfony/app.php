<?php

require_once __DIR__ . '/../setUp.php';

$env = getenv('SYMFONY_ENV');

if (empty($env)) {
    if (!empty($_SERVER['REQUEST_METHOD'])) {
        // define SYMFONY_ENV and SYMFONY_DEBUG in apache config
        // something like
        //		SetEnv SYMFONY_ENV dev
        //		SetEnv SYMFONY_DEBUG 1
        throw new \Exception("Error occurred, code #OXF934");
    } else {
        $env = "dev";
    }
}

if ($env == 'dev' && isset($_SERVER['HTTP_X_SYMFONY_ENV'])) {
    $env = $_SERVER['HTTP_X_SYMFONY_ENV'];
}

$debug = $env != 'prod' && $env != 'staging' && getenv('SYMFONY_DEBUG') !== '0';

require_once __DIR__ . '/../AppKernel.php';

// register session write handler before symfony boot, to save changes before symfony close session in NativeSessionStorage::__construct: session_register_shutdown
$closeSessionHandler = null;
register_shutdown_function(function () use (&$closeSessionHandler) {
    if (!empty($closeSessionHandler)) {
        call_user_func($closeSessionHandler);
    }
});

if ($debug) {
    \Symfony\Component\ErrorHandler\Debug::enable();
}

$liteSymfony = new AppKernel($env, $debug);
$liteSymfony->boot();
/** @var \Symfony\Component\DependencyInjection\ContainerInterface $symfonyContainer */
global $symfonyContainer, $bNoSession;
$symfonyContainer = $liteSymfony->getContainer();
\AwardWallet\MainBundle\FrameworkExtension\ContainerConstants::define($symfonyContainer);
$handler = set_exception_handler('var_dump');
$handler = \is_array($handler) ? $handler[0] : null;
restore_exception_handler();

if ($handler instanceof \Symfony\Component\ErrorHandler\ErrorHandler) {
    $handler->setDefaultLogger($symfonyContainer->get("logger"), -1);
}

if (php_sapi_name() != 'cli') {
    // we will emulate symfony request lifecycle
    $server = $_SERVER;

    if ($symfonyContainer->getParameter("requires_channel") == 'https') {
        $server['HTTPS'] = 'on';
        $server['HTTP_X_FORWARDED_PROTO'] = 'https';
    }

    // REMOTE_ADDR will be passed from true-client-ip header, see fastcgi-symfony.conf and CloudFront edge function
    // \Symfony\Component\HttpFoundation\Request::setTrustedProxies(['127.0.0.1', $_SERVER['REMOTE_ADDR']], \Symfony\Component\HttpFoundation\Request::HEADER_X_FORWARDED_AWS_ELB);
    $request = \Symfony\Component\HttpFoundation\Request::create($symfonyContainer->get('router')->generate('aw_oldsite_bootfirewall'), 'GET', $_GET, $_COOKIE, $_FILES, $server);
    $request->attributes->set('_original_query', $_GET);
    $request->attributes->set('_original_server', $server);
    $request->attributes->set('_route', 'aw_oldsite_bootfirewall');
    $symfonyContainer->get('request_stack')->push($request);

    if (pageWantsSession()) {
        $dispatcher = $symfonyContainer->get('event_dispatcher');

        // prevent web debug toolbar to interfere with old pages
        if ($symfonyContainer->has('profiler')) {
            $symfonyContainer->get('profiler')->disable();
        }
        // boot firewall
        $event = new \Symfony\Component\HttpKernel\Event\RequestEvent($liteSymfony, $request, \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST);
        $dispatcher->dispatch(\Symfony\Component\HttpKernel\KernelEvents::REQUEST, $event);

        $closeSessionHandler = function () use ($dispatcher, $request, $liteSymfony, &$bNoSession) {
            // set cookies (remember-me) and save session after registration / login
            if (!$bNoSession) {
                $response = new \Symfony\Component\HttpFoundation\Response();
                $event = new \Symfony\Component\HttpKernel\Event\ResponseEvent($liteSymfony, $request, \Symfony\Component\HttpKernel\HttpKernelInterface::MASTER_REQUEST, $response);
                $dispatcher->dispatch(\Symfony\Component\HttpKernel\KernelEvents::RESPONSE, $event);

                foreach ($event->getResponse()->headers->getCookies() as $cookie) {
                    setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
                }

                foreach ($event->getResponse()->headers as $key => $value) {
                    if (stripos($key, 'X-Aw') === 0) {
                        header($key . ': ' . implode("; ", $value));
                    }
                }
            }
        };
    }
}
