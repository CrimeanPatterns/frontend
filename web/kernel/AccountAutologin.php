<?php

require_once __DIR__."/../wsdl/awardwallet/AwardWalletService.php";

class AccountAutologin {
	
	public $wsdlServer 					= null;
	public $wsdlLogin					= null;
	public $wsdlPassword				= null;
	public $wsdlBasicAuthLogin			= null;
	public $wsdlBasicAuthPassword		= null;

	/** @var \AwardWallet\MainBundle\Loyalty\ApiCommunicator */
	protected $communicator;

	public function __construct() {
		$this->wsdlServer = getSymfonyContainer()->getParameter("wsdl.address");
		$this->wsdlLogin = getSymfonyContainer()->getParameter("wsdl.login");
		$this->wsdlPassword = getSymfonyContainer()->getParameter("wsdl.password");
		$this->wsdlBasicAuthLogin = (defined('DEBUG_PROXY_HTTPAUTH_USERNAME')) ? DEBUG_PROXY_HTTPAUTH_USERNAME : null;
		$this->wsdlBasicAuthPassword = (defined('DEBUG_PROXY_HTTPAUTH_PASSWORD')) ? DEBUG_PROXY_HTTPAUTH_PASSWORD : null;

		$this->communicator = getSymfonyContainer()->get(\AwardWallet\MainBundle\Loyalty\ApiCommunicator::class);
	}
	
	public function getAutologinFrame($providerCode, $login, $login2, $login3, $pass, $targetUrl, $userID = 'test', $targetType = null, $startUrl = null, $userData = null)
    {
        $request = (new \AwardWallet\MainBundle\Loyalty\Resources\AutoLoginRequest())
                    ->setProvider($providerCode)
                    ->setLogin($login)
                    ->setLogin2($login2)
                    ->setLogin3($login3)
                    ->setPassword($pass)
                    ->setUserId($userID)
                    ->setUserData($userData)
                    ->setTargetUrl($targetUrl)
                    ->setStartUrl($startUrl)
                    ->setSupportedProtocols(['http', 'https']);

        try {
            $response = $this->communicator->AutoLogin($request);
        } catch (\AwardWallet\MainBundle\Loyalty\ApiCommunicatorException $e) {
            return null;
        }

        return $response->getResponse();
    }

}

