<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

require_once __DIR__.'/setUp.php';
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
