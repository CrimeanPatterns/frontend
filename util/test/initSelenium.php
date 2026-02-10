<?
require_once __DIR__ . '/../../web/kernel/public.php';

global $capabilities;
$capabilities = DesiredCapabilities::chrome();
$driver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
