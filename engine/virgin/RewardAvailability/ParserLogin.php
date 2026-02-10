<?php

namespace AwardWallet\Engine\virgin\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use CheckRetryNeededException;
use Facebook\WebDriver\Exception\InvalidSelectorException;
use Facebook\WebDriver\Exception\InvalidSessionIdException;
use Facebook\WebDriver\Exception\SessionNotCreatedException;
use Facebook\WebDriver\Exception\WebDriverException;
use Symfony\Component\HttpClient\Exception\TransportException;
use SeleniumFinderRequest;
use WebDriverBy;
use AwardWallet\Engine\virgin\QuestionAnalyzer;

class ParserLogin extends \TAccountChecker
{
    // parser is almost the same as delta
    use \SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;

    private const ATTEMPTS_CNT = 4;
    private const BROWSER_STATISTIC_KEY = 'ra_virgin_statistBr';
    public static bool $useParserOld = false;


    private $airportDetails = [];
    private $cacheKey;
    private $supportedCurrencies = ['USD'];

    private $config;

    public static function GetAccountChecker($accountInfo)
    {
        $debugMode = $accountInfo['DebugState'] ?? false;
        if (!$debugMode) {
            require_once __DIR__ . "/ParserOld.php";

            return new ParserOld();
        }
        return new static();
    }

    public static function getRASearchLinks(): array
    {
        return ['https://www.virginatlantic.com/us/en' => 'search page'];
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies' => $this->supportedCurrencies,
            'supportedDateFlexibility' => 0,
            'defaultCurrency' => 'USD',
            'priceCalendarCabins'      => ["firstClass", "business", "premiumEconomy", "economy", "unknown"],
        ];
    }

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();
        $this->UseSelenium();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->setDefaultHeader("Upgrade-Insecure-Requests", "1");
        $this->http->setDefaultHeader("Connection", "keep-alive");
        $this->http->setDefaultHeader('Accept-Encoding', 'gzip, deflate, br');

        switch (1) {
            case 0:
                $this->useChromePuppeteer(SeleniumFinderRequest::CHROME_PUPPETEER_103);

                break;

            default:
                $this->useFirefoxPlaywright(SeleniumFinderRequest::FIREFOX_PLAYWRIGHT_101);

                break;
        }

        $this->http->saveScreenshots = true;
        $array     = ['de', 'us', 'ca', 'fi', 'au', 'fr'];
        $targeting = $array[array_rand($array)];

        $this->setProxyGoProxies(null, $targeting);

        $this->seleniumRequest->setHotSessionPool(self::class, $this->AccountFields['ProviderCode']);
    }

    public function IsLoggedIn()
    {
        $this->http->GetURL('https://www.virginatlantic.com/en-EU');
        if ($this->waitForElement(WebDriverBy::xpath("//span[@data-testid='typography-component'][contains(., 'Hello')]"), 10)) {
            return true;
        }

        if ($cookie = $this->waitForElement(WebDriverBy::xpath('//button[@id="ensAcceptAll"]'), 10)) {
            $cookie->click();
        }
        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->GetURL('https://www.virginatlantic.com/flights/search/slice?origin=LHR&destination=JFK&departing=2025-05-24&passengers=a1t0c0i0&awardSearch=true');

        if ($this->waitForElement(WebDriverBy::xpath('//input[@id="signInName"]'),20)){
            $this->saveResponse();
            return true;
        }

        $this->saveResponse();
        return true;
    }

    public function Login()
    {
        $login = $this->waitForElement(WebDriverBy::xpath('//input[@id="signInName"]'),10);
        $pass = $this->waitForElement(WebDriverBy::xpath('//input[@id="password"]'),0);
        $continueBtn = $this->waitForElement(WebDriverBy::xpath('//button[@id="continue"]'),0);

        if (!$login || !$pass || !$continueBtn) {
            throw new \CheckRetryNeededException(5, 0);
        }

        $this->logger->debug('Login Send Keys!');
        $login->clear();
        $login->sendKeys($this->AccountFields['Login']);

        $this->logger->debug('Password Send Keys!');
        $pass->clear();
        $pass->sendKeys($this->AccountFields['Pass']);

        $this->saveResponse();

        $this->logger->debug('Continue button');
        $continueBtn->click();

        if ($button = $this->waitForElement(WebDriverBy::xpath('//button[@id="readOnlyEmail_ver_but_send"]'),10)) {
            $button->click();
        }

        if ($this->waitForElement(WebDriverBy::xpath('//input[@id="readOnlyEmail_ver_input"]'),5)) {
            return $this->parseQuestion();
        }

        $this->saveResponse();
        if ($this->waitForElement(WebDriverBy::xpath("//span[@data-testid='typography-component'][contains(., 'Hello')]"), 20)) {
            return true;
        } else {
            $this->saveResponse();
            return true;
        }
    }

    private function parseQuestion(): bool
    {
        $this->logger->notice(__METHOD__);

        $this->saveResponse();
        $question = $this->http->FindSingleNode('//div[contains(., "We\'ve sent you a code to verify your details") and @id="readOnlyEmail_info"]');
        $this->saveResponse();
        $this->logger->debug($question);

        if (QuestionAnalyzer::isOtcQuestion($question)) {
            $this->logger->info("Two Factor Authentication Login", ['Header' => 3]);

            $this->holdSession();
            $this->question = $question;

            $this->AskQuestion($this->question, null, 'Question');

            return false;
        }

        return true;
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);

        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);

        $oneTimeCodeInput = $this->waitForElement(WebDriverBy::xpath('//input[@id="readOnlyEmail_ver_input"]'),5);
        $contBtn = $this->waitForElement(WebDriverBy::xpath('//button[@id="readOnlyEmail_ver_but_verify"]'),0);

        if (!$oneTimeCodeInput || !$contBtn) {
            throw new \CheckRetryNeededException(5, 0);
        }

        $oneTimeCodeInput->click();
        $oneTimeCodeInput->sendKeys($answer);
        $this->saveResponse();

        $contBtn->click();
        $this->saveResponse();

        if ($this->waitForElement(WebDriverBy::xpath("//span[@data-testid='typography-component'][contains(., 'Hello')]"), 30)) {
            return true;
        } else {
            $this->saveResponse();
            return true;
        }

    }

        public function ParseCalendar(array $fields)
    {
        $this->logger->info("Parse Calendar", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        if ($fields['DepDate'] > strtotime('+331 day')) {
            $this->SetWarning('Ah - something\'s not right here. We can only show you flights up to 331 days in advance - can you also check the return date is after the departure date');

            return [];
        }

        if ($this->waitForElement(WebDriverBy::xpath('//input[@id="signInName"]'),10)) {
            $this->Login();
        }

        sleep(10);
        $this->saveResponse();

//
//        if (!$this->validRouteAll($fields)) {
//            return ['fares' => []];
//        }

//        $this->loadBookPage();

        return ["fares" => []];

    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        if ($fields['Adults'] > 9) {
            $this->logger->info('Error in params');
            $this->logger->error("It's too much travellers");

            return ['routes' => []];
        }

        if ($fields['DepDate'] > strtotime('+331 day')) {
            $this->SetWarning('Ah - something\'s not right here. We can only show you flights up to 331 days in advance - can you also check the return date is after the departure date');

            return [];
        }
        return ["routes" => []];

        if (!$this->validRouteAll($fields)) {
            return ['routes' => []];
        }


        return ["routes" => []];
    }

    private function loadBookPage(): ?string
    {
        $this->logger->notice(__METHOD__);

        $url = 'https://www.virginatlantic.com/flights/search/slice?origin=LHR&destination=JFK&departing=2025-05-24&passengers=a1t0c0i0&awardSearch=true';
    }


    private function validRouteAll($fields)
    {
        $this->logger->notice(__METHOD__);

        $airports           = \Cache::getInstance()->get('ra_virgin_airports');
        $airportDesc        = \Cache::getInstance()->get('ra_virgin_airportDesc');
        $airportCountryCode = \Cache::getInstance()->get('ra_virgin_airportCountry');

        if (!$airports || !is_array($airports) || !$airportDesc || !is_array($airportDesc) || !$airportCountryCode || !is_array($airportCountryCode)) {
            $airports           = [];
            $airportDesc        = [];
            $airportCountryCode = [];

            $browser = new \HttpBrowser("none", new \CurlDriver());

            $browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
            $browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
            $browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));
            $this->http->brotherBrowser($browser);

            $browser->RetryCount = 0;

            $browser->GetURL("https://www.virginatlantic.com/util/airports/ALL/asc", [], 20);

            if ($browser->currentUrl() === 'https://www.virginatlantic.com/gb/en/error/system-unavailable1.html') {
                // it's work
                $browser->GetURL("https://www.virginatlantic.com/util/airports/ALL/asc", [], 20);
            }
            $data = $browser->JsonLog(null, 1);

            if ($browser->currentUrl() === 'https://www.virginatlantic.com/gb/en/error/system-unavailable1.html') {
                $this->markProxyAsInvalid();

                throw new \CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
            }

            $browser->RetryCount = 2;

            if (strpos($browser->Error,
                    'Network error 56 - Received HTTP code 407 from proxy after CONNECT') !== false
                || strpos($browser->Error,
                    'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
                || strpos($browser->Error, 'Network error 28 - Operation timed out after ') !== false
                || $browser->Response['code'] == 403
            ) {
                $this->markProxyAsInvalid();

                $browser->cleanup();
                throw new CheckRetryNeededException(self::ATTEMPTS_CNT, 0);
            }

            if (!isset($data->listOfCities) || !is_array($data->listOfCities)) {

                $browser->cleanup();
                return true;
            }

            if (empty($data->listOfCities)) {

                $browser->cleanup();
                return true;
            }

            foreach ($data->listOfCities as $city) {
                $airports[]                             = $city->airportCode;
                $airportDesc[$city->airportCode]        = $city->cityName . ', ' . $city->region;
                $airportCountryCode[$city->airportCode] = $city->countryCode;
            }

            if (!empty($airports)) {
                \Cache::getInstance()->set('ra_virgin_airports', $airports, 60 * 60 * 24);
                \Cache::getInstance()->set('ra_virgin_airportDesc', $airportDesc, 60 * 60 * 24);
                \Cache::getInstance()->set('ra_virgin_airportCountry', $airportCountryCode, 60 * 60 * 24);
            }
        }

        if (!empty($airports) && !in_array($fields['DepCode'], $airports)) {
            $this->SetWarning('no flights from ' . $fields['DepCode']);

            $browser->cleanup();
            return false;
        }

        if (!empty($airports) && !in_array($fields['ArrCode'], $airports)) {
            $this->SetWarning('no flights to ' . $fields['ArrCode']);

            $browser->cleanup();
            return false;
        }

        $this->airportDetails = [
            $fields['DepCode'] => [
                'desc' => $airportDesc[$fields['DepCode']],
                'country' => $airportCountryCode[$fields['DepCode']]
            ],
            $fields['ArrCode'] => [
                'desc' => $airportDesc[$fields['ArrCode']],
                'country' => $airportCountryCode[$fields['ArrCode']]
            ],
        ];

        $browser->cleanup();
        return true;
    }

}
