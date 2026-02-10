<?php

// This is global bootstrap for autoloading
include_once __DIR__ . '/../app/autoload.php';

include_once __DIR__ . '/_modules/Utils/ClosureEvaluator.php';

include_once __DIR__ . '/_modules/Utils/Reflection.php';

// do not clean global vars from this file
include_once __DIR__ . '/../bundles/AwardWallet/MainBundle/FrameworkExtension/Mailer/html2text.php';

\Codeception\Util\Autoload::addNamespace('', __DIR__ . DIRECTORY_SEPARATOR . '_pages');
\Codeception\Util\Autoload::addNamespace('', __DIR__ . DIRECTORY_SEPARATOR . '_data');
\Codeception\Util\Autoload::addNamespace('', __DIR__ . DIRECTORY_SEPARATOR . '/_modules');
\Codeception\Util\Autoload::addNamespace('', __DIR__ . DIRECTORY_SEPARATOR . '/_support');
