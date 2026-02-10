<?php
error_reporting(E_ALL &~ E_DEPRECATED & ~E_STRICT);

global $sPath, $QS;
if( !isset( $sPath ) )
	$sPath = realpath(__DIR__."/../web");
$QS = $_GET;

require_once __DIR__.'/../vendor/autoload.php';

if (!function_exists('loadProviderChecker')) {
	function loadProviderChecker($className) {
		if(strpos($className, 'TAccountChecker') === 0){
			$code = strtolower(substr($className, strlen('TAccountChecker')));
			if(!empty($code)) {
				$file = __DIR__ . '/../engine/' . $code . '/functions.php';
				if(file_exists($file))
					require_once $file;
			}
		}
	}
	spl_autoload_register('loadProviderChecker');
}

if (!defined('STDERR')) { define('STDERR', fopen('php://stderr', 'w')); }

require_once( __DIR__."/../web/lib/functions.php" );

global $DieAsException, $DieTraceOnWarning;

$DieAsException = 'fatal';
$DieTraceOnWarning = [\AwardWallet\MainBundle\FrameworkExtension\DieTraceUtils::class, "DieTraceOnWarning"];

require_once( __DIR__."/../web/kernel/siteFunctions.php" );
require_once( __DIR__."/../web/lib/constants.php" );
if (!defined('PERSISTENT'))
	define('PERSISTENT', true);
require_once( __DIR__."/../web/kernel/constants.php" );
require_once( __DIR__."/../vendor/awardwallet/service/old/constants.php" );
require_once( __DIR__."/../web/lib/classes/TQuery.php" );
require_once( __DIR__."/../vendor/awardwallet/service/old/functions.php" );
require_once( __DIR__."/../web/lib/geoFunctions.php" );
require_once( __DIR__."/../web/account/common.php" );
require_once( __DIR__."/../web/lib/textFunctions.php" );

define("EMAIL_RICH_HEADERS", "MIME-Version: 1.0\n" . str_ireplace("text/plain", "text/HTML", EMAIL_HEADERS));
$Config[CONFIG_TRAVEL_PLANS] = true;
//$Config[CONFIG_SITE_STATE] = SITE_STATE_DEBUG;

// receive deciphered https from balancer
if(ConfigValue(CONFIG_THROUGH_PROXY)){
	if(isset($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']))
		$_SERVER['HTTP_X_FORWARDED_PROTO'] = $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'];
	if(isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
	&& ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
	&& (!isset($_SERVER['HTTPS']) || ($_SERVER['HTTPS'] != 'on'))) {
		$_SERVER['HTTPS'] = 'on';
		$_SERVER['SERVER_PORT'] = $_SERVER['HTTP_PORT'] = 443;
	}
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $proxies = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        if (count($proxies) > 1) {
            array_unshift($proxies, $clientIp = array_pop($proxies));
            $_SERVER['HTTP_X_FORWARDED_FOR'] = implode(', ', $proxies); // refs #16079
        } else {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
    }
}

// correct HTTP/1.0
if(!isset($_SERVER['HTTP_HOST']) && isset($_SERVER['SCRIPT_URI'])){
	$url = parse_url($_SERVER['SCRIPT_URI']);
	if($url !== false)
		$_SERVER['HTTP_HOST'] = $url['host'];
}

if(ConfigValue(CONFIG_HTTPS_ONLY))
	ini_set('session.cookie_secure', 'true');
ini_set('session.cookie_httponly', 'true');

global $NO_DATABASE, $Connection;
if(!isset($NO_DATABASE))
	$Connection = new SymfonyMysqlConnection();

// prevent empty\invalid session_id
if (!empty($_COOKIE) && array_key_exists($sessionName = ini_get('session.name'), $_COOKIE)) {
    $sessionId = $_COOKIE[$sessionName];
    if ('' === $sessionId || !preg_match('/[a-z0-9-,]+/i', $sessionId)) {
        unset($_COOKIE[$sessionName]);
    }
}
