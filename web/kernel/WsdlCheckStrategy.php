<?php

require_once __DIR__."/../wsdl/awardwallet/AwardWalletService.php";

class WsdlCheckStrategy implements AccountAuditorCheckStrategyInterface {
	
	const WSDL_ASYNC_TYPE	= 0;
	const WSDL_SYNC_TYPE	= 1;
	
	public $wsdlServer					= null;
	public $wsdlLogin					= null;
	public $wsdlPassword				= null;
	public $wsdlCallback				= '';
	public $wsdlType					= null;
	public $wsdlTimeout					= 50;
	public $wsdlBasicAuthLogin			= null;
	public $wsdlBasicAuthPassword		= null;
	public $wsdlPriority				= 7;
	public $wsdlRetries					= 0;
	public $wsdlAsyncWait				= true;
	public $wsdlSleepTimeInterval		= 5; // async type
	public $wsdlWaitingTime				= 150; // async type
	
	protected $params = array();
	protected $callback = '';
	protected $markCoupons = null;
	
	public $connection;
	
	public function __construct ($connection = null) {
		global $Connection;
		if (isset($connection))
			$this->connection = $connection;
		else
			$this->connection = $Connection;

		$this->wsdlServer = getSymfonyContainer()->getParameter("wsdl.address");
		$this->wsdlLogin = getSymfonyContainer()->getParameter("wsdl.login");
		$this->wsdlPassword = getSymfonyContainer()->getParameter("wsdl.password");
		$this->wsdlCallback = getSymfonyContainer()->getParameter("wsdl.callback_address");
		$this->wsdlType = WsdlCheckStrategy::WSDL_ASYNC_TYPE;
		$this->wsdlBasicAuthLogin = (defined('DEBUG_PROXY_HTTPAUTH_USERNAME')) ? DEBUG_PROXY_HTTPAUTH_USERNAME : null;
		$this->wsdlBasicAuthPassword = (defined('DEBUG_PROXY_HTTPAUTH_PASSWORD')) ? DEBUG_PROXY_HTTPAUTH_PASSWORD : null;
		$this->createWsdlParams();
	}
	
	/**
	 * Mark daily deals coupons
	 * TODO: experimental function
	 * 
	 * @param array $coupons array(id_coupon => used?, 12345 => false, 123457 => true)
	 * @return void
	 */
	public function markCoupons($coupons) {
		$this->markCoupons = $coupons;
	}
	
	public function check(Account $account, AuditorOptions $options) {
		global $Connection;
		# Only single account
		$account = $this->validateAccount($account);
		# Account info
		$accountInfo = $account->getAccountInfo();
		# Report
		$report = new AccountCheckReport();
		$report->balance = null;
		$report->errorCode = ACCOUNT_ENGINE_ERROR;
		$report->errorMessage = 'Unknown error';
		$report->errorReason = null;
		$report->debugInfo = null;
		$report->properties = array();
		$report->properties['ParseIts'] = $options->checkIts;
		if(isset($options->timeout))
			$this->wsdlWaitingTime = $options->timeout;
		if(isset($options->wsdlTimeout))
			$this->wsdlTimeout = $options->wsdlTimeout;
		if($this->wsdlWaitingTime == 0)
			$this->wsdlAsyncWait = false;

		ini_set('default_socket_timeout', 300);
		$this->createWsdlParams();
		# Mark coupons
		$coupons = null;
		if (isset($this->markCoupons) && is_array($this->markCoupons) && sizeof($this->markCoupons) > 0) {
			$coupons = $this->createMarkCouponsObjects($this->markCoupons);
		}
		# Answers
		$answers = $this->createAnswersObjects($accountInfo['AccountID']);
		$this->connection->Execute("UPDATE Account SET QueueDate = ADDDATE(NOW(), 7)
		WHERE AccountID = {$accountInfo['AccountID']}");
		# Options
		$checkOptions = self::getCheckOptions($accountInfo);
		if ($options->keepLogs and array_search('keepLogs', $checkOptions) === false)
			$checkOptions[] = 'keepLogs';
        if(!empty($options->source))
            $checkOptions[] = 'Source' . $options->source;
		if(aaPasswordValid($accountInfo))
			$accountInfo["Pass"] = '';
//		if($accountInfo['ProviderID'] == AA_PROVIDER_ID && $accountInfo['ErrorCode'] == ACCOUNT_CHECKED && !empty($accountInfo['Pass']))
//			DieTrace("aa password check", false, 0, $account);
		# AwardWallet Service
		$startTime = microtime(true);
		$lastUpdateDate = Lookup("Account", "AccountID", "UpdateDate", $accountInfo['AccountID'], true);
		if(!empty($lastUpdateDate))
			$lastUpdateDate = $Connection->SQLToDateTime($lastUpdateDate);
		else
			$lastUpdateDate = time();
		$aw = $this->createService();
		try {
			// try 3 times to handle connect errors
			for($n = 0; $n < 3 && (microtime(true) - $startTime) < 59; $n++){
				try{
					$request = new CheckAccountRequest(
						WSDL_API_VERSION,
						$accountInfo['ProviderCode'],
						$accountInfo["Login"],
						$accountInfo["Login2"],
						$accountInfo["Login3"],
                        preg_replace('/[\x{0000}-\x{0019}]+/ums', '', $accountInfo["Pass"]), // remove unicode special chars, like \u0007
						true,
						$this->wsdlTimeout,
						$this->wsdlPriority,
						$this->callback,
						$this->wsdlRetries,
						$options->checkIts,
						$accountInfo["UserID"],
						$accountInfo["AccountID"],
						$coupons,
						$answers,
						$accountInfo['BrowserState'],
						$options->checkHistory, // we will check history only in new design and from background
						null,
						null,
						false,
						null,
						null,
						implode(",", $checkOptions)
					);
					$response = $aw->CheckAccount($request);
					AccountAuditor::requestSent($request);
					// successfull check, exit loop
					break;
				}
				catch (SoapFault $e) {
					// retry only on connect error
					if($e->getMessage() != "Error Fetching http headers")
						throw $e;
				}
				sleep(2);
			}
			if(!isset($response))
				throw new \AccountException('Timed out', ACCOUNT_TIMEOUT);
			if ($response instanceof CheckAccountResponse && $response->ErrorCode == SOAP_TIMEOUT && $this->wsdlType == self::WSDL_ASYNC_TYPE && $this->wsdlAsyncWait) {
				$saved = $this->periodicChecking($accountInfo['AccountID'], $startTime, $this->wsdlWaitingTime, $this->wsdlSleepTimeInterval, $lastUpdateDate);
				return $saved;
			} elseif ($response instanceof CheckAccountResponse && ($response->ErrorCode == SOAP_TIMEOUT || $response->State == ACCOUNT_TIMEOUT)) {
				throw new \AccountException('Timed out', ACCOUNT_TIMEOUT);
			} elseif ($response instanceof CheckAccountResponse && in_array($response->ErrorCode, array(SOAP_BAD_LOGIN, SOAP_BAD_PASSWORD, SOAP_BAD_LOGIN2, SOAP_BAD_LOGIN3))) {
				throw new \AccountException($response->ErrorMessage, $response->ErrorCode);
			} elseif ($response instanceof CheckAccountResponse && $response->ErrorCode != SOAP_SUCCESS) {
				throw new \RuntimeException($response->ErrorMessage, $response->ErrorCode);
			} elseif ($response instanceof CheckAccountResponse) {
				$service = new CallbackService();
				$service->autoSave = false;
				$service->CheckAccountCallback($response);
				$resultChecking = $service->convertCheckAccountResponse;
			} else {
				throw new \RuntimeException('Invalid response: '.$accountInfo['ProviderCode'].'-'.$accountInfo["Login"].'-'.$accountInfo["UserID"]);
			}
		} catch (SoapFault $e) {
			if($e->getMessage() == 'looks like we got no XML document')
				mailTo(ConfigValue(CONFIG_ERROR_EMAIL), 'wrong xml received', $aw->__getLastResponse(), EMAIL_HEADERS);
			else
				DieTrace($e->getMessage()." [".$e->getCode()."]", true, 0, "Time taken: ".round(microtime(true) - $startTime, 3)." sec\n".$aw->__getLastResponseHeaders().$aw->__getLastResponse());
		}
		
		# Validate result checking
		if (isset($resultChecking) && $resultChecking instanceof AccountCheckReport) {
			$report = $resultChecking;
			$report->properties['ParseIts'] = $options->checkIts;
		}
		
		return $report;
	}

	/**
	 * @param array $accountInfo
	 * @return array
	 */
	public static function getCheckOptions(array $accountInfo){
		$checkOptions = [];
        $providerCode = ArrayVal($accountInfo, "ProviderCode", ArrayVal($accountInfo, "Code"));
		if($accountInfo['SavePassword'] == SAVE_PASSWORD_LOCALLY && $providerCode != 'aa' && $providerCode != 'capitalcards') {
			$checkOptions[] = 'keepLogs';
			$checkOptions[] = 'LocalPassword';
		}
		if(aaPasswordValid($accountInfo)){
			$checkOptions[] = 'DontCheckPassword';
		}
		if (ArrayVal($accountInfo, 'ProviderGroupCheck', false)) {
			$checkOptions[] = ACCOUNT_REQUEST_OPTION_PROVIDER_GROUP_CHECK;
		}
		return $checkOptions;
	}
	
	protected function createWsdlParams() {
		$this->params = array(
			'exceptions' 		=> true,
			'trace' 			=> true,
			'location'	 		=> $this->wsdlServer,
			'connection_timeout'=> 300,
			'wsse-login' 		=> $this->wsdlLogin,
			'wsse-password' 	=> $this->wsdlPassword,
		);
		$this->params['connection_timeout'] = ($this->wsdlType == self::WSDL_SYNC_TYPE) ? $this->wsdlTimeout : 0;
		$this->callback = ($this->params['connection_timeout'] == 0) ? $this->wsdlCallback : '';
		if (isset($this->wsdlBasicAuthLogin, $this->wsdlBasicAuthPassword)) {
			$this->params["login"] = $this->wsdlBasicAuthLogin;
			$this->params["password"] = $this->wsdlBasicAuthPassword;
		}
	}
	
	protected function createMarkCouponsObjects($coupons) {
		$result = array();
		foreach ($coupons as $couponId => $used) {
			$result[] = new MarkUsedCouponType($used, $couponId);
		}
		return $result;
	}
	
	protected function createAnswersObjects($accountID) {
		$answerQuery = new TQuery("SELECT * FROM Answer WHERE AccountID = {$accountID} and Valid = 1", $this->connection);
		$answers = null;
		if (!$answerQuery->EOF) {
			$answers = array();
			while (!$answerQuery->EOF) {
				$answers[] = new AnswerType($answerQuery->Fields['Question'], $answerQuery->Fields['Answer']);
				$answerQuery->Next();
			}
		}
		return $answers;
	}

    /**
     * @param $account
     * @return AccountInterface
     */
	protected function validateAccount($account) {
		if ($account instanceof AccountInterface)
			return $account;
	}
	
	protected function periodicChecking($accountID, $startTime, $waitingTime, $interval, $lastUpdateDate) {
		global $Connection;
		$sql = "SELECT 1 FROM Account WHERE AccountID = {$accountID} AND UpdateDate > ".$Connection->DateTimeToSQL($lastUpdateDate);
		while (time() - $startTime < $waitingTime) {
			$q = new TQuery($sql, $this->connection);
			if (!$q->EOF)
				return true;
			sleep($interval);
		}
		throw new \AccountException('Timed out', ACCOUNT_TIMEOUT);
	}

	public function createService(){
		return new AwardWalletService($this->params, __DIR__."/../wsdl/awardwallet/services.wsdl");
	}
	
}

