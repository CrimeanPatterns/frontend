<pre>
<?php
	require "../kernel/public.php";
	require_once "$sPath/../bundles/AwardWallet/MainBundle/Email/GoogleOauth.php";
	require_once "$sPath/../bundles/AwardWallet/MainBundle/Email/Scanner.php";
	require_once "$sPath/../bundles/AwardWallet/MainBundle/Email/MailboxExplorer.php";
	use AwardWallet\MainBundle\Email\GoogleOauth;

	function _log($o) {
		if (is_array($o))
			var_export($o);
		else
			echo $o;
		echo "\n";
	}

	function foo($parser) {
		return array (
			"Subject" => $parser->getSubject(),
			"Date" => $parser->getHeader('Date'),
		);
	}

	$google = new GoogleOauth();
	$google->redirect_url = "http://{$_SERVER["HTTP_HOST"]}/admin/testOauth.php";

	?>
	<a href="<?=$google->consentUrl()?>">Sign in with Gmail</a>
	<?
	if (isset($_GET["atoken"]))
		$google->access_token = $_GET["atoken"];
	if (isset($_GET["rtoken"]))
		$google->refresh_token = $_GET["rtoken"];
	if (isset($_GET['code'])) {
		$google->code = $_GET['code'];
		_log('Received code: '.$google->code);
		if ($google->requestToken()) {
			_log('Requesting token SUCCESS');
		}
		else {
			_log('Requesting token FAILED');
		}
	}
	if (isset($google->access_token))
		_log('access token: '.$google->access_token);
	if (isset($google->refresh_token))
		_log('refresh_token: '.$google->refresh_token);
	if (isset($google->access_token)) {
		$email = $google->getUserEmail();
		_log('User email: '.$email);
		if (isset($_GET["revoke"])) {
			_log('Revoking token '.($google->revokeToken() ? 'SUCCESS' : 'FAILED'));
		}
	}
	if (isset($google->refresh_token)) {
		if (isset($_GET["refresh"])) {
			if ($google->refreshToken()) {
				_log('Refresh token SUCCESS');
				_log('New access_token: '.$google->access_token);
			}
			else {
				_log('Refresh token FAILED');
			}
		}
	}
	if (isset($google->access_token) && isset($_GET["scan"]) && isset($email)) {
		$scanner = new \AwardWallet\MainBundle\Email\Scanner();
		$scanner->xoauthOpen($email, $google->access_token);
		_log('Scanning for SUBJECT "Rapid Rewards"');
		$results = $scanner->scan('SUBJECT "Rapid Rewards"', 'foo');
		_log($results);
	}
	$url = "";
	if (isset($google->access_token))
		$url .= "atoken=".$google->access_token."&";
	if (isset($google->refresh_token))
		$url .= "rtoken=".$google->refresh_token."&";
	if ($url != "") {
		$url = "testOauth.php?".trim($url, "&");
		?>
		<a href="<?=$url?>">Test Userinfo API</a>
		<a href="<?=$url?>&refresh">Refresh token</a>
		<a href="<?=$url?>&revoke">Revoke token</a>
		<a href="<?=$url?>&scan">Scan for Southwest emails</a>
		<?}?>
</pre>