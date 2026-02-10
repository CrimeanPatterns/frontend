<?php

if (strpos($_SERVER['DOCUMENT_URI'], "..") === false && strpos($_SERVER['DOCUMENT_URI'], ":") === false) { // overreacting, actually nginx will normalize path
    if (substr($_SERVER['DOCUMENT_URI'], -4) === '.php') {
        $script = realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['DOCUMENT_URI']);
    } elseif (substr($_SERVER['DOCUMENT_URI'], -1) === '/') {
        $script = realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['DOCUMENT_URI'] . "index.php");
    }

    if (isset($script) && file_exists($script) && strpos($script, $_SERVER['DOCUMENT_ROOT']) === 0) {
        chdir(dirname($script));
        $_SERVER['SCRIPT_FILENAME'] = $script;
        $_SERVER['SCRIPT_NAME'] = substr($script, strlen($_SERVER['DOCUMENT_ROOT']));
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

        require $script;

        exit();
    }
}

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../app/autoload.php';

require_once __DIR__ . '/../app/setUp.php';

require_once __DIR__ . '/../app/AppKernel.php';

$env = getenv('SYMFONY_ENV');

if (!in_array($env, ['staging', 'prod', 'acceptance'])) {
    $env = 'prod';
}

$kernel = new AppKernel($env, false);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
