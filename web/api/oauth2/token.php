<?php
require __DIR__."/../../kernel/public.php";

/**
 * token endpoint.
 */

$locked = getSymfonyContainer()->get("aw.security.antibruteforce.password")->checkForLockout("oauth_" . $_SERVER['REMOTE_ADDR']);
if(!empty($locked))
	die($locked);

$cache = getSymfonyContainer()->get(Memcached::class);
if (
    $cache->add("oauth_token_" . ($_POST['code'] ?? ''), time(), 3600)
    && $cache->getResultCode() === \Memcached::RES_NOTSTORED
) {
    die("code reused");
}

require_once "AWOAuth2.php";

$oauth = new AWOAuth2();
$oauth->grantAccessToken();
