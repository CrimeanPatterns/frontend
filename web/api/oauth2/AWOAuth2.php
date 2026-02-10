<?php

require_once __DIR__ . "/../../lib/3dParty/oauth2-php/lib/OAuth2.php";

/**
 * OAuth2 Library AW Implementation.
 */
class AWOAuth2 extends OAuth2
{
	protected $userId;

	function __construct(){
		parent::__construct(array(
			'auth_code_lifetime' => 60,
			'access_token_lifetime' => SECONDS_PER_DAY * 365,
		));
	}

	/**
	 * Implements OAuth2::checkClientCredentials().
	 */
	protected function checkClientCredentials($client_id, $client_secret = NULL)
	{
		$q = new TQuery("select * from OA2Client where Login = '" . addslashes($client_id) . "'
      	and Pass = '" . addslashes($client_secret) . "'");
		return !$q->EOF;
	}

	/**
	 * Implements OAuth2::getRedirectUri().
	 */
	protected function getRedirectUri($client_id)
	{
		$q = new TQuery("select * from OA2Client where Login = '" . addslashes($client_id) . "'");
		if ($q->EOF)
			return FALSE;
		return $q->Fields['RedirectURL'];
	}

	/**
	 * Implements OAuth2::getAccessToken().
	 */
	protected function getAccessToken($oauth_token)
	{
		global $Connection;
		$q = new TQuery("select c.Login, t.*
		from OA2Token t
		join OA2Client c on t.OA2ClientID = c.OA2ClientID
		where t.Token = '" . addslashes($oauth_token) . "'");
		if($q->EOF)
			return null;
		return array(
			'client_id' => $q->Fields['Login'],
			'expires' => $Connection->SQLToDateTime($q->Fields['Expires']),
			'scope' => $q->Fields['Scope']
		);
	}

	/**
	 * Implements OAuth2::setAccessToken().
	 */
	protected function setAccessToken($oauth_token, $client_id, $expires, $scope = NULL)
	{
		global $Connection;
		if(!isset($this->userId))
			DieTrace("Unauthorized");
		$client = new TQuery("SELECT * FROM OA2Client where Login = '".addslashes($client_id)."'");
		if($client->EOF)
			die("client not found");
		$Connection->Execute(InsertSQL("OA2Token", array(
			"Token" => "'".addslashes($oauth_token)."'",
			"OA2ClientID" => $client->Fields['OA2ClientID'],
			"Expires" => $Connection->DateTimeToSQL(time() + $client->Fields['AccessTokenLifetime']),
			"Scope" => "'".addslashes($scope)."'",
			"UserID" => $this->userId,
		)));
	}

	/**
	 * Overrides OAuth2::getSupportedGrantTypes().
	 */
	protected function getSupportedGrantTypes()
	{
		return array(
			OAUTH2_GRANT_TYPE_AUTH_CODE,
		);
	}

	/**
	 * Overrides OAuth2::getAuthCode().
	 */
	protected function getAuthCode($code)
	{
		global $Connection;
		$q = new TQuery("select c.Login, co.*
		from OA2Code co
		join OA2Client c on co.OA2ClientID = c.OA2ClientID
		where co.Code = '" . addslashes($code) . "'");
		if($q->EOF)
			return null;
		$this->userId = $q->Fields['UserID'];
		return array(
			'client_id' => $q->Fields['Login'],
			'redirect_uri' => $q->Fields['RedirectURL'],
			'expires' => $Connection->SQLToDateTime($q->Fields['Expires']),
			'scope' => $q->Fields['Scope']
		);
	}

	/**
	 * Overrides OAuth2::setAuthCode().
	 */
	protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = NULL)
	{
		global $Connection;
		if(!isset($_SESSION['UserID']))
			DieTrace("Unauthorized");
		$clientId = Lookup("OA2Client", "Login", "OA2ClientID", "'".addslashes($client_id)."'", true);
		$Connection->Execute(InsertSQL("OA2Code", array(
			"Code" => "'".addslashes($code)."'",
			"OA2ClientID" => $clientId,
			"RedirectURL" => "'".addslashes($redirect_uri)."'",
			"Expires" => $Connection->DateTimeToSQL($expires),
			"Scope" => "'".addslashes($scope)."'",
			"UserID" => $_SESSION['UserID'],
		)));
	}

    protected function getSupportedScopes() {
	    $result = ['accounts'];
	    if (getSymfonyContainer()->get("security.authorization_checker")->isGranted('ROLE_STAFF')) { // TODO: ROLE_MANAGE_DEBUG_PROXY
	        $result[] = 'debugProxy';
        }
        return $result;
    }

}
