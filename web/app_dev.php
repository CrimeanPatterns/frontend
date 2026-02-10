<?php

if (strpos($_SERVER['DOCUMENT_URI'], "..") === false && strpos($_SERVER['DOCUMENT_URI'], ":") === false) { // overreacting, actually nginx will normalize path
    if (substr($_SERVER['DOCUMENT_URI'], -4) === '.php') {
        $script = realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['DOCUMENT_URI']);
    }
    elseif (substr($_SERVER['DOCUMENT_URI'], -1) === '/') {
        $script = realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['DOCUMENT_URI'] . "index.php");
    }
    if(isset($script) && file_exists($script) && strpos($script, $_SERVER['DOCUMENT_ROOT']) === 0 && $script !== ($_SERVER['DOCUMENT_ROOT'] . "app_dev.php")){
        chdir(dirname($script));
        $_SERVER['SCRIPT_FILENAME'] = $script;
        $_SERVER['SCRIPT_NAME'] = substr($script, strlen($_SERVER['DOCUMENT_ROOT']));
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
        require $script;
        exit();
    }
}

use Symfony\Component\HttpFoundation\Request;

$env = getenv('SYMFONY_ENV');
if ($env != 'dev') {
	// define SYMFONY_ENV and SYMFONY_DEBUG in apache config
	// something like
	//		SetEnv SYMFONY_ENV=dev
	//		SetEnv SYMFONY_DEBUG=1
	header("X-Error-Code: E402825", true, 403);
	die("Access denied, code E402825");
}

if(isset($_SERVER['HTTP_X_SYMFONY_ENV']))
    $env = $_SERVER['HTTP_X_SYMFONY_ENV'];

//if(stripos($_SERVER['REQUEST_URI'], 'client-info') === false) {
//    var_dump($_SERVER);
//    die();
//}
//

require_once __DIR__.'/../app/autoload.php';
require_once __DIR__.'/../app/setUp.php';

require_once __DIR__.'/../app/AppKernel.php';

$isDebug = ($env == 'dev');

if ($isDebug) {
    \Symfony\Component\ErrorHandler\Debug::enable();
}

$kernel = new AppKernel($env, $isDebug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
