<?php

// Here you can initialize variables that will for your tests
include_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'app/setUp.php';

\Codeception\Util\Autoload::addNamespace('TestSymfonyGuy', __DIR__ . DIRECTORY_SEPARATOR . '_steps');
