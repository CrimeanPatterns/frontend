<?
// -----------------------------------------------------------------------
// main site include
//		it's recommened to start each site page with this include
//		contains site configuration and other includes
//		should not contain any functions (use siteFunctions.php)
// -----------------------------------------------------------------------
set_time_limit(120);

// init
if(isset($_SERVER['REQUEST_METHOD']))
	ob_start();

require_once __DIR__.'/../../app/setUp.php';

$sTitle = "No title";
$cPage = "";
$bSecuredPage = True;

$Config["dateNote"] = "April 1, 1980 would look like \"04/01/1980\"";

if(isset($_SERVER['REQUEST_METHOD']))
	NoCache();

global $symfonyContainer;
getSymfonyContainer();
$Config[CONFIG_HTTPS_ONLY] = $symfonyContainer->getParameter('requires_channel') == 'https';

if(pageWantsSession()){
	$symfonyContainer->get("session")->has("boot");
}

Trace();

// create other objects
if(NDInterface::enabled())
	$Interface = new NDInterface();
else
	$Interface = New TInterface;

processRefCode();

$Interface->RedirectToLogin();

function processRefCode(){
	if(isset($_GET["invId"]))
		$_SESSION["invId"] = intval( $_GET["invId"] );
	if(isset($_GET["invrId"]))
		$_SESSION["invrId"] = intval( $_GET["invrId"] );
	if(isset($_GET["refCode"]) && (trim($_GET["refCode"]) != '')){
		$q = new TQuery("select * from Usr limit 1");
		if(array_key_exists("RefCode", $q->Fields)){
			$q = new TQuery("select UserID from Usr where RefCode = '".addslashes($_GET['refCode'])."'");
			if(!$q->EOF){
				$_SESSION['invrId'] = $q->Fields['UserID'];
				$_SESSION['ref'] = 4; // invite option on left bar
			}
		}
	}
}

$Interface->Init();

mb_internal_encoding('UTF-8');

// debug
if((ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_DEBUG) && isset($Connection) && isset($_GET['SQLTrace']))
	$Connection->Tracing = true;

// regional settings
initRegionalSettings();

$Config[CONFIG_TRAVEL_PLANS] = true;
if(isset($_SERVER['REQUEST_METHOD'])){
	header("P3P: CP=\"CAO PSA OUR\"");
	header("Content-Type: text/html; charset=UTF-8");
	//header("X-UA-Compatible: IE=8");
	//header("X-UA-Compatible: IE=EmulateIE7");
}
$sBodyOnLoad = "";

if(isset($_SESSION['UserID']) && isset($Connection)){
	// is business manager
    unset($_SESSION['AdminOfBusinessAccount']);
    if(isBusinessAccountAdmin($_SESSION['UserID']))
        $_SESSION['AdminOfBusinessAccount'] = true;
        
    // Check and update AccountLevel (per minute)
    if (!isset($_SESSION['UpdateUserInfo']) || $_SESSION['UpdateUserInfo'] < time()-60) {
    	$_SESSION['UpdateUserInfo'] = time();
    	$q = new TQuery("SELECT `AccountLevel` FROM `Usr` WHERE `UserID` = '".$_SESSION['UserID']."' LIMIT 1");
    	if (!$q->EOF) {
    		$_SESSION['UserFields']['AccountLevel'] = $q->Fields['AccountLevel'];
    		$_SESSION['AccountLevel'] = $q->Fields['AccountLevel'];
    	}
    }
}

# #4356, Personal UserID in business
if (SITE_MODE == SITE_MODE_BUSINESS) {
	if (isset($_SESSION['UserID']) && isset($_SESSION['AccountLevel']) && $_SESSION['AccountLevel'] <> ACCOUNT_LEVEL_BUSINESS) {
		DieTrace('Personal account in business', false);
	}
}

if(php_sapi_name() == 'cli')
	getSymfonyContainer()->get("logger")->pushHandler(new \Monolog\Handler\StreamHandler('php://stderr', \Monolog\Logger::CRITICAL));