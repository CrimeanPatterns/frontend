<?php

// Here you can initialize variables that will for your tests
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app/setUp.php';

\Codeception\Util\Autoload::addNamespace('TestGuy\Mobile', __DIR__ . DIRECTORY_SEPARATOR . '_steps/Mobile');
// \Codeception\Util\Autoload::register('', 'Mobile\\\\\w+Steps', __DIR__.DIRECTORY_SEPARATOR.'_steps'.DIRECTORY_SEPARATOR.'Mobile');
