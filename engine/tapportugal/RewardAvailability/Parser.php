<?php

namespace AwardWallet\Engine\tapportugal\RewardAvailability;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use CheckException;
use CheckRetryNeededException;
use WebDriverBy;

class Parser extends \TAccountChecker
{
    use \SeleniumCheckerHelper;
    use \PriceTools;
    use ProxyList;

    public $isRewardAvailability = true;
    private $sessionToken;
    private $dataResponseOnlyTap;
    private $dataResponseAlliance;
    private $bodyResponseOnlyTap;
    private $bodyResponseAlliance;
    private $hasTapOnly;
    private $noRoute;

    private $tapRoute;
    private $starAllianceRoute;
    private $changedCabin;
    private $login = false;

    public static function getRASearchLinks(): array
    {
        return ['https://booking.flytap.com/booking' => 'search page'];
    }

    public static function GetAccountChecker($accountInfo)
    {
//        $debugMode = $accountInfo['DebugState'] ?? false;
//
//        if (!$debugMode) {
//            require_once __DIR__ . "/ParserOld.php";
//
//            return new ParserOld();
//        }

        return new static();
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->UseSelenium();

        $this->KeepState = true;
        $this->debugMode = $this->AccountFields['DebugState'] ?? false;

        $this->http->saveScreenshots = true;
        $this->disableImages();
        $this->useCache();
        $this->usePacFile(false);

        $this->setProxyGoProxies(null, 'pt');

//            $this->useChromium();
//            $this->useChromePuppeteer(\SeleniumFinderRequest::CHROME_PUPPETEER_103);
        $this->useFirefox(\SeleniumFinderRequest::FIREFOX_100);


        if (!isset($this->State["Resolution"])) {
            $resolutions = [
                [1360, 768],
                [1920, 1080],
            ];
            $this->State["Resolution"] = $resolutions[array_rand($resolutions)];
        }
        $this->setScreenResolution($this->State["Resolution"]);

        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumRequest->setOs(\SeleniumFinderRequest::OS_LINUX);
        $request = FingerprintRequest::firefox();
        $request->platform = "Linux x86_64";
        $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

        if ($fingerprint !== null) {
            $this->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
            $this->http->setUserAgent($fingerprint->getUseragent());
            $this->seleniumOptions->setResolution([
                $fingerprint->getScreenWidth(),
                $fingerprint->getScreenHeight(),
            ]);
        }

        $this->seleniumRequest->setHotSessionPool(self::class, $this->AccountFields['ProviderCode']);
    }

    public function IsLoggedIn()
    {
        try {
            $this->http->GetURL("https://booking.flytap.com/booking", [], 15);
        } catch (\TimeOutException | \UnknownServerException | \NoSuchDriverException $e) {
            $this->logger->error("Exception: " . $e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        } catch (\UnexpectedAlertOpenException | \Facebook\WebDriver\Exception\UnknownErrorException $e) {
            $this->logger->error("Exception: " . $e->getMessage());

            try {
                $this->http->GetURL("https://booking.flytap.com/booking");
            } catch (\UnexpectedAlertOpenException | \Facebook\WebDriver\Exception\UnknownErrorException | \WebDriverException  $e) {
                throw new CheckRetryNeededException(5, 0);
            }
        } catch (\Facebook\WebDriver\Exception\WebDriverException | \WebDriverException  $e) {
            throw new CheckRetryNeededException(5, 0);
        }

        $this->saveResponse();

        if ($this->http->FindSingleNode('//span[contains(., "Bad gateway")]')) {
            throw new \CheckException('HOST ERROR', ACCOUNT_PROVIDER_ERROR);
        }

        if ($this->isBadProxy()) {
            $this->DebugInfo = "bad proxy";
            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(5, 0);
        }

        $this->acceptCookie();

        $logo = $this->waitForElement(WebDriverBy::xpath("//a//*[@alt='TAP Air Portugal logo'] | //*[@class='flight-actions__item flight-search']"), 30);

        if ($this->waitForElement(\WebDriverBy::xpath("//p[contains(normalize-space(), 'Estamos fazendo melhorias em nosso mecanismo de reservas. Pedimos desculpas pela inconveniência.')]"), 0)) {
            throw new \CheckException('Estamos fazendo melhorias em nosso mecanismo de reservas. Pedimos desculpas pela inconveniência.', ACCOUNT_PROVIDER_ERROR);
        }

        if ($this->waitForElement(\WebDriverBy::xpath("//h1[contains(text(),'Voltaremos em breve')]"), 0)) {
            throw new \CheckException('Technical works.', ACCOUNT_PROVIDER_ERROR);
        }

        if (!$logo) {
            throw new \CheckRetryNeededException(5, 0);
        }

        try {
            $script = "return sessionStorage.getItem('userData');";
            $this->saveResponse();
            $this->logger->debug("[run script]");
            $this->logger->debug($script, ['pre' => true]);
            $userData = $this->driver->executeScript($script);
        } catch (\UnknownServerException $e) {
            $this->logger->error('UnknownServerException: ' . $e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        } catch (\WebDriverException | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error("WebDriverException: " . $e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        }

        if (!empty($userData)) {
            $this->logger->debug("logged in");

            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        if ($this->attempt != 0) {

            try {
                $this->http->GetURL("https://booking.flytap.com/booking", [], 15);
            } catch (\TimeOutException | \UnknownServerException | \NoSuchDriverException $e) {
                $this->logger->error("Exception: " . $e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            } catch (\UnexpectedAlertOpenException | \Facebook\WebDriver\Exception\UnknownErrorException $e) {
                $this->logger->error("Exception: " . $e->getMessage());

                try {
                    $this->http->GetURL("https://booking.flytap.com/booking");
                } catch (\UnexpectedAlertOpenException | \Facebook\WebDriver\Exception\UnknownErrorException | \WebDriverException  $e) {
                    throw new CheckRetryNeededException(5, 0);
                }
            } catch (\Facebook\WebDriver\Exception\WebDriverException | \WebDriverException  $e) {
                throw new CheckRetryNeededException(5, 0);
            }

            try {
                $script = "return sessionStorage.getItem('userData');";
                $this->saveResponse();
                $this->logger->debug("[run script]");
                $this->logger->debug($script, ['pre' => true]);
                $userData = $this->driver->executeScript($script);
            } catch (\UnknownServerException $e) {
                $this->logger->error('UnknownServerException: ' . $e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            } catch (\WebDriverException|\Facebook\WebDriver\Exception\WebDriverException $e) {
                $this->logger->error("WebDriverException: " . $e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            }
            if (!empty($userData)) {
                $this->logger->debug("logged in");

                return $this->login = true;
            }
        }

        if ($this->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login' or normalize-space()='header.text.logIn']"),
            25)) {

            if ($this->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='header.text.logIn']"), 0)) {
                try {
                    $this->http->GetURL("https://booking.flytap.com/booking");
                    $this->saveResponse();

                    // 502 Bad Gateway
                    if ($this->http->FindSingleNode('//h1[contains(text(), "502 Bad Gateway")]')) {
                        if ($this->attempt == 0) {
                            throw new CheckRetryNeededException(5, 0);
                        }

                        throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                    }

                    if ($this->isBadProxy()) {
                        $this->markProxyAsInvalid();

                        throw new CheckRetryNeededException(5, 0);
                    }
                } catch (\TimeOutException $e) {
                    $this->logger->error("Exception: " . $e->getMessage());

                    throw new CheckRetryNeededException(5, 0);
                } catch (\WebDriverException | \Facebook\WebDriver\Exception\WebDriverException $e) {
                    $this->logger->error("WebDriverException: " . $e->getMessage());

                    throw new CheckRetryNeededException(5, 0);
                }
            }

            if ($this->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='header.text.logIn']"), 10)) {
                $this->logger->error('header.text.logIn');

                throw new CheckRetryNeededException(5, 0);
            }
        }

        $this->acceptCookie();

        if (!$this->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login' or normalize-space()='header.text.logIn']"), 0)) {
            try {
                $this->logger->debug("[run js]: document.querySelector('#pay-miles').click();");
                $this->driver->executeScript("document.querySelector('#pay-miles').click();");
            } catch (\UnexpectedJavascriptException
            | \UnknownCommandException $e) {
                $this->logger->error('UnexpectedJavascriptException: ' . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            } catch (\Facebook\WebDriver\Exception\JavascriptErrorException $e) {
                $this->logger->error('JavascriptErrorException: ' . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            } catch (\Facebook\WebDriver\Exception\WebDriverException $e) {
                $this->logger->error('WebDriverException: ' . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }
        }

        if ($btn = $this->waitForElement(\WebDriverBy::xpath("//a[normalize-space()='Login']"), 5)) {
            $this->logger->debug("login click");

            try {
                $btn->click();
            } catch (\UnrecognizedExceptionException $e) {
                $this->logger->error('UnrecognizedExceptionException: ' . $e->getMessage());
                $this->saveResponse();

                throw new \CheckRetryNeededException(5, 0);
            } catch (\Facebook\WebDriver\Exception\ElementClickInterceptedException $e) {
                $this->driver->executeScript("document.querySelector('.header-fallback__user-name').click();");
            } catch (\WebDriverException | \Facebook\WebDriver\Exception\WebDriverException $e) {
                $this->logger->error("WebDriverException: " . $e->getMessage());

                throw new CheckRetryNeededException(5, 0);
            }
        }

        return true;
    }

    public function Login()
    {
        $this->logger->notice(__METHOD__);
        $this->saveResponse();

        if ($this->login) {
            return true;
        }

        $login = false;

        $loginInput = $this->waitForElement(\WebDriverBy::xpath('//input[@id="login"]'), 10, false);
        $passwordInput = $this->waitForElement(\WebDriverBy::xpath('//input[@id="login-password"]'), 0, false);
        $button = $this->waitForElement(\WebDriverBy::xpath("//button[@type='submit'][normalize-space()='Login' or normalize-space()='Log in' or normalize-space()='header.text.logIn']"), 0);

        if (!$loginInput || !$passwordInput || !$button) {
            $this->saveResponse();
            $this->logger->error('login form not load');

            return null;
        }

        if (!isset($this->AccountFields['Login']) || !isset($this->AccountFields['Pass'])) {
            throw new CheckRetryNeededException(5, 0);
        }

        $loginInput->sendKeys($this->AccountFields['Login']);
        $passwordInput->sendKeys($this->AccountFields['Pass']);

        if (!$button) {
            $this->saveResponse();

            return null;
        }

        try {
            $button->click();
        } catch (\Facebook\WebDriver\Exception\ElementClickInterceptedException $e) {
            $this->driver->executeScript("document.querySelector('button[type=\"submit\"]').click();");
        } catch (\WebDriverException | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error("WebDriverException: " . $e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        }

        if ($this->waitForElement(\WebDriverBy::xpath("//span[contains(normalize-space(), 'Login temporarily unavailable.') or contains (normalize-space(), 'Login temporariamente indisponível')]"), 10)) {
            $counter = \Cache::getInstance()->get('ra_tapportugal_failed_auth');
            $this->saveResponse();

            if (!$counter) {
                $counter = 0;
            }
            $counter++;
            \Cache::getInstance()->set('ra_tapportugal_failed_auth', $counter, 10 * 60); // 10min

            throw new \CheckException('Login temporariamente indisponível.', ACCOUNT_PROVIDER_ERROR);
        }

        try {
            $this->saveResponse();
        } catch (\UnknownCommandException $e) {
            $this->logger->error('UnknownCommandException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\WebDriverException | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error("WebDriverException: " . $e->getMessage());

            throw new CheckRetryNeededException(5, 0);
        }

        if ($this->waitForElement(\WebDriverBy::xpath("//span[contains(normalize-space(), 'Login temporarily unavailable.') or contains (normalize-space(), 'Login temporariamente indisponível')]"), 10)) {
            $counter = \Cache::getInstance()->get('ra_tapportugal_failed_auth');

            if (!$counter) {
                $counter = 0;
            }
            $counter++;
            \Cache::getInstance()->set('ra_tapportugal_failed_auth', $counter, 10 * 60); // 10min

            throw new \CheckException('Login temporariamente indisponível.', ACCOUNT_PROVIDER_ERROR);
        }

        $sleep = 25;
        $startTime = time();

        $scriptUserData = "return sessionStorage.getItem('userData');";
        $this->logger->debug("[script scriptUserData]");
        $this->logger->debug($scriptUserData, ['pre' => true]);

        while (((time() - $startTime) < $sleep) && !$login) {
            $this->logger->debug("(time() - \$startTime) = " . (time() - $startTime) . " < {$sleep}");

            if ($this->waitForElement(WebDriverBy::xpath('(//div[contains(@class,"header-fallback__user")][normalize-space()!="Login"])[1]'), 0, false)) {
                $login = true;
                $this->saveResponse();

                break;
            }
            $this->logger->debug("[run script scriptUserData]");
            $userData = $this->driver->executeScript($scriptUserData);

            if (!empty($userData)) {
                $this->logger->debug("logged in");
                $login = true;
                $this->saveResponse();

                break;
            }

            if ($message = $this->waitForElement(WebDriverBy::xpath("//h5[contains(.,'Login Error')]/following-sibling::div[1][contains(.,'Algo correu mal. Tente mais tarde.')]"), 0)) {
                if ($this->attempt >= 3 || (time() - $this->requestDateTime) > 90) {
                    throw new \CheckException($message->getText(), ACCOUNT_PROVIDER_ERROR);
                }

                if ($message = $this->waitForElement(WebDriverBy::xpath("//span[contains(.,'Login temporarily unavailable')]"),
                    0)) {
                    throw new \CheckException($message->getText(), ACCOUNT_PROVIDER_ERROR);
                }

                throw new CheckRetryNeededException(5, 0);
            }

            if ($message = $this->waitForElement(WebDriverBy::xpath("//span[contains(.,'Login temporarily unavailable')]"),
                0)) {
                throw new \CheckException($message->getText(), ACCOUNT_PROVIDER_ERROR);
            }

            if ($message = $this->waitForElement(WebDriverBy::xpath("//div[contains(@class,'form-errors')]"), 0)
            ) {
                $error = $this->http->FindPreg("/^[\w.]*\:\:?\s*([^<]+)/ims", false, $message->getText());

                if (!$error) {
                    $error = $message->getText();
                }
                $this->logger->error($error);

                if (strpos($error,
                        "Sorry, it is currently not possible to validate the information provided. Please try again later.") !== false
                    || strpos($error,
                        "E-mail ou número de cliente (TP): Campo obrigatório") !== false
                    || strpos($error,
                        "Lamentamos, mas de momento não é possível validar as informações fornecidas. Por favor, tente novamente mais tarde.") !== false
                    || strpos($error, "header.login.errorLoginRequiredOrInvalid") !== false
                    || strpos($error, "header.login.error.userUnknown") !== false
                    || strpos($error, "O login do utilizador que inseriu não é válido") !== false
                    || strpos($error, "Palavra-passe: campo obrigatório.") !== false
                ) {
                    throw new CheckRetryNeededException(5, 0);
                }
                $this->sendNotification('check msg // ZM');

                break;
            }

            if ($message = $this->waitForElement(WebDriverBy::xpath("//span[contains(.,'O estado da sua conta não lhe permite aceder a este link. Por favor, contacte o serviço de apoio Miles & Go')]"), 0)
            ) {
                $msg = $message->getText();
                $this->logger->error($msg);

                if ($this->attempt >= 3 || (time() - $this->requestDateTime) > 90) {
                    throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                }

                throw new CheckRetryNeededException(5, 0);
            }

            if ($message = $this->waitForElement(WebDriverBy::xpath("//*[contains(normalize-space(text()),'Login temporariamente indisponível')]"), 0)) {
                $msg = $message->getText();
                $this->logger->error($msg);
                $this->sendNotification('check msg // ZM');

                if ($this->attempt >= 3 || (time() - $this->requestDateTime) > 90) {
                    throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                }

                throw new CheckRetryNeededException(5, 0);
            }
            $this->saveResponse();
        }

        if (!$login) {
            $script = "return sessionStorage.getItem('userData');";
            $this->logger->debug("[run script]");
            $this->logger->debug($script, ['pre' => true]);
            $userData = $this->driver->executeScript($script);

            if (!empty($userData)) {
                $this->logger->debug("logged in");
                $login = true;
                $this->saveResponse();
            }
        }

        try {
            $this->driver->executeScript("
            if (document.querySelector('#pay-miles') && !document.querySelector('#pay-miles').checked)
                document.querySelector('#pay-miles').click();
        ");
        } catch (\UnexpectedJavascriptException $e) {
            $this->logger->error('UnexpectedJavascriptException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }
        $this->saveResponse();

        return $login;
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['EUR'],
            'supportedDateFlexibility' => 0,
            'defaultCurrency'          => 'EUR',
            'priceCalendarCabins'      => ["unknown"],
        ];
    }

    public function ParseCalendar(array $fields)
    {
        $this->logger->info("Parse Calendar", ['Header' => 2]);
        $this->logger->debug('Params: ' . var_export($fields, true));

        $warningMsg = null;

        if ($fields['DepDate'] > strtotime('+360 day')) {
            $this->SetWarning('You checked too late date');

            return ['fares' => []];
        }

        $supportedCurrencies = $this->getRewardAvailabilitySettings()['supportedCurrencies'];

        if (!in_array($fields['Currencies'][0], $supportedCurrencies)) {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        $origins = \Cache::getInstance()->get('ra_tapportugal_origins');

        if (is_array($origins) && !in_array($fields['DepCode'], $origins)) {
            $this->SetWarning('No flights from ' . $fields['DepCode']);

            return ['fares' => []];
        }

        if ($fields['Adults'] > 9) {
            $this->SetWarning("It's too much travellers");

            return ['fares' => []];
        }
        $counter = \Cache::getInstance()->get('ra_tapportugal_failed_auth');

        if ($counter && $counter > 100 && !$this->waitForElement(WebDriverBy::xpath('(//div[contains(@class,"header-fallback__user")][normalize-space()!="Login"])[1]'), 0)) {
            $this->logger->error('10 min downtime is on');

            throw new \CheckException('Login temporariamente indisponível.', ACCOUNT_PROVIDER_ERROR);
        }

        $sessionToken = $this->getToken();

        $hasTapOnly = $this->checkRouteData($fields, $sessionToken);

        if ($this->noRoute) {
            $this->logger->notice('Data ok, saving session');
            $this->keepSession(true);

            return ['fares' => []];
        }

        if (isset($this->tapRoute, $this->starAllianceRoute)) {
            if ($this->tapRoute) {
                $this->dataResponseOnlyTap = $this->tryAjax($fields, $sessionToken, false);
            }

            if ($this->starAllianceRoute) {
                $this->dataResponseAlliance = $this->tryAjax($fields, $sessionToken);
            }
        } else {
            $this->dataResponseAlliance = $this->tryAjax($fields, $sessionToken);

            if ($hasTapOnly) {
                $this->dataResponseOnlyTap = $this->tryAjax($fields, $sessionToken, false);
            }
        }

        $this->logger->info('TAP + ALLIANCE');

        $data = null;

        if (!empty($this->dataResponseAlliance)) {
            $data = $this->dataResponseAlliance;
            $this->http->SetBody($this->bodyResponseAlliance);
        } elseif (!isset($this->tapRoute, $this->starAllianceRoute) || $this->starAllianceRoute) {
            $data = $this->otherTypeSearch($fields, true);
        }
        $calendar = [];

        if (isset($data)) {
            if (empty($data->data)) {
                if (isset($data->errors[0])) {
                    $warningMsg = $this->http->FindPreg('/"desc":"(NO ITINERARY FOUND FOR REQUESTED SEGMENT.+?)"/') ??
                        $this->http->FindPreg('/"desc":"(No available flight found for the requested segment.+?)"/') ??
                        $this->http->FindPreg('#"desc":"(Unknown City/Airport)"#') ??
                        $this->http->FindPreg('#"desc":"(Bad value \(coded\) - timeDetails)"#') ??
                        $this->http->FindPreg('/"desc":"(NO\s+FARE\s+FOUND\s+FOR\s+REQUESTED\s+ITINERARY)"/m');

                    if (!$warningMsg) {
                        if (strpos($this->http->Response['body'],
                                '"desc":"Transaction unable to process') !== false
                            || strpos($this->http->Response['body'],
                                '"code":"404","type":"ERROR"') !== false
                            || strpos($this->http->Response['body'],
                                '"code":"Read timed out","type":"ERROR","desc":"404"') !== false
                        ) {
                            throw new CheckRetryNeededException(5, 0);
                        }
                        $unknownErrorFromTapAlliance = $data->errors[0];
                        $this->logger->error('mem error on tap+alliance');
                    }
                } else {
                    if ($this->http->Response['code'] == 403) {
                        throw new CheckRetryNeededException(5, 0);
                    }

                    throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                }
            } elseif (empty($data->data->outPanel)) {
                $warningMsg = 'Select another date with available flights';
            } else {
                $calendar = $this->parseCalendarJson($data, $fields, );
            }
        }

        $this->logger->info('TAP ONLY');
        $data = null;

        if (!empty($this->dataResponseOnlyTap)) {
            $data = $this->dataResponseOnlyTap;
            $this->http->SetBody($this->bodyResponseOnlyTap);
        } elseif ($this->hasTapOnly || !isset($this->tapRoute, $this->starAllianceRoute)) {
            $data = $this->otherTypeSearch($fields);
        }

        if (isset($data)) {
            if (empty($data->data)) {
                if (isset($data->errors[0])) {
                    $warningMsg1 = $this->http->FindPreg('/"desc":"(NO ITINERARY FOUND FOR REQUESTED SEGMENT.+?)"/') ??
                        $this->http->FindPreg('/"desc":"(No available flight found for the requested segment.+?)"/') ??
                        $this->http->FindPreg('#"desc":"(Unknown City/Airport)"#') ??
                        $this->http->FindPreg('#"desc":"(Bad value \(coded\) - timeDetails)"#') ??
                        $this->http->FindPreg('/"desc":"(Read timed out)/') ??
                        $this->http->FindPreg('/"desc":"(NO\s+FARE\s+FOUND\s+FOR\s+REQUESTED\s+ITINERARY)"/m');

                    if (!$warningMsg1 && (empty($calendar) || empty($warningMsg) || isset($unknownErrorFromTapAlliance))) {
                        if (empty($calendar)) {
                            if (strpos($this->http->Response['body'], '"desc":"Transaction unable to process') !== false) {
                                throw new CheckRetryNeededException(5, 0);
                            }
                            $this->sendNotification('check error (2) // ZM');

                            throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                        }
                        $this->sendNotification('check error (3) // ZM');
                    } elseif ($warningMsg1 && empty($warningMsg)) {
                        $warningMsg = $warningMsg1;
                    }
                } elseif (empty($calendar)) {
                    if ($this->http->Response['code'] == 403) {
                        throw new CheckRetryNeededException(5, 0);
                    }

                    throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                }
            } elseif (empty($data->data->outPanel)) {
                $warningMsg = 'Select another date with available flights';
            } else {
                $calendarOnlyTap = $this->parseCalendarJson($data, $fields);

                if (!empty($calendarOnlyTap) && $calendarOnlyTap != $calendar) {
                    $allRoutes = array_merge($calendarOnlyTap, $calendar);
                    $calendar = array_map('unserialize', array_unique(array_map('serialize', $allRoutes)));
                }
            }
        }

        if (isset($this->changedCabin) && count($this->changedCabin) === 2 && isset($this->tapRoute, $this->starAllianceRoute) && $this->tapRoute && $this->starAllianceRoute && empty($warningMsg)) {
            $this->logger->notice('possible duplicates with different cabins'); // так-то руками не находились, но на всякий влог отметка
        }

        if (empty($calendar) && !empty($warningMsg)) {
            if ($warningMsg === "Read timed out") {
                throw new \CheckException($warningMsg, ACCOUNT_PROVIDER_ERROR);
            }

            if ($warningMsg === "Bad value (coded) - timeDetails") {
                $warningMsg = 'Select another date with available flights';
            }
            $this->SetWarning($warningMsg);
        }

        return ['fares' => $calendar];
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        if ($fields['DepDate'] > strtotime('+360 day')) {
            $this->SetWarning('You checked too late date');

            return ['routes' => []];
        }
        $warningMsg = null;

        $supportedCurrencies = $this->getRewardAvailabilitySettings()['supportedCurrencies'];

        if (!in_array($fields['Currencies'][0], $supportedCurrencies)) {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }

        $origins = \Cache::getInstance()->get('ra_tapportugal_origins');

        if (is_array($origins) && !in_array($fields['DepCode'], $origins)) {
            $this->SetWarning('No flights from ' . $fields['DepCode']);

            return ['routes' => []];
        }

        if ($fields['Adults'] > 9) {
            $this->SetWarning("It's too much travellers");

            return ['routes' => []];
        }
        $counter = \Cache::getInstance()->get('ra_tapportugal_failed_auth');

        if ($counter && $counter > 100 && !$this->waitForElement(WebDriverBy::xpath('(//div[contains(@class,"header-fallback__user")][normalize-space()!="Login"])[1]'), 0)) {
            $this->logger->error('10 min downtime is on');

            throw new \CheckException('Login temporariamente indisponível.', ACCOUNT_PROVIDER_ERROR);
        }

        if (!$fields['ParseCalendar']) {
            $sessionToken = $this->getToken();

            $hasTapOnly = $this->checkRouteData($fields, $sessionToken);

            if ($this->noRoute) {
                $this->logger->notice('Data ok, saving session');
                $this->keepSession(true);

                return ['routes' => []];
            }

            if (isset($this->tapRoute, $this->starAllianceRoute)) {
                if ($this->tapRoute) {
                    $this->dataResponseOnlyTap = $this->tryAjax($fields, $sessionToken, false);
                }

                if ($this->starAllianceRoute) {
                    $this->dataResponseAlliance = $this->tryAjax($fields, $sessionToken);
                }
            } else {
                $this->dataResponseAlliance = $this->tryAjax($fields, $sessionToken);

                if ($hasTapOnly) {
                    $this->dataResponseOnlyTap = $this->tryAjax($fields, $sessionToken, false);
                }
            }
        }

        $this->logger->notice('Data ok, saving session');
        $this->keepSession(true);
        // New search
        $this->logger->info('TAP + ALLIANCE');

        $data = null;

        if (!empty($this->dataResponseAlliance)) {
            $data = $this->dataResponseAlliance;
            $this->http->SetBody($this->bodyResponseAlliance);
        } elseif (!isset($this->tapRoute, $this->starAllianceRoute) || $this->starAllianceRoute) {
            $data = $this->otherTypeSearch($fields, true);
        }
        $routes = [];

        if (isset($data)) {
            if (empty($data->data)) {
                if (isset($data->errors[0])) {
                    $warningMsg = $this->http->FindPreg('/"desc":"(NO ITINERARY FOUND FOR REQUESTED SEGMENT.+?)"/') ??
                        $this->http->FindPreg('/"desc":"(No available flight found for the requested segment.+?)"/') ??
                        $this->http->FindPreg('#"desc":"(Unknown City/Airport)"#') ??
                        $this->http->FindPreg('#"desc":"(Bad value \(coded\) - timeDetails)"#') ??
                        $this->http->FindPreg('/"desc":"(NO\s+FARE\s+FOUND\s+FOR\s+REQUESTED\s+ITINERARY)"/m');

                    if (!$warningMsg) {
                        if (strpos($this->http->Response['body'],
                                '"desc":"Transaction unable to process') !== false
                            || strpos($this->http->Response['body'],
                                '"code":"404","type":"ERROR"') !== false
                            || strpos($this->http->Response['body'],
                                '"code":"Read timed out","type":"ERROR","desc":"404"') !== false
                        ) {
                            throw new CheckRetryNeededException(5, 0);
                        }
                        $unknownErrorFromTapAlliance = $data->errors[0];
                        $this->logger->error('mem error on tap+alliance');
                    }
                } else {
                    if ($this->http->Response['code'] == 403) {
                        throw new CheckRetryNeededException(5, 0);
                    }

                    throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                }
            } elseif (empty($data->data->offers)) {
                $warningMsg = 'Select another date with available flights';
            } else {
                $routes = $this->parseRewardFlights($data, $fields, true);
            }
        }

        $this->logger->info('TAP ONLY');
        $data = null;

        if (!empty($this->dataResponseOnlyTap)) {
            $data = $this->dataResponseOnlyTap;
            $this->http->SetBody($this->bodyResponseOnlyTap);
        } elseif ($this->hasTapOnly || !isset($this->tapRoute, $this->starAllianceRoute)) {
            $data = $this->otherTypeSearch($fields, false);
        }

        if (isset($data)) {
            if (empty($data->data)) {
                if (isset($data->errors[0])) {
                    $warningMsg1 = $this->http->FindPreg('/"desc":"(NO ITINERARY FOUND FOR REQUESTED SEGMENT.+?)"/') ??
                        $this->http->FindPreg('/"desc":"(No available flight found for the requested segment.+?)"/') ??
                        $this->http->FindPreg('#"desc":"(Unknown City/Airport)"#') ??
                        $this->http->FindPreg('#"desc":"(Bad value \(coded\) - timeDetails)"#') ??
                        $this->http->FindPreg('/"desc":"(Read timed out)/') ??
                        $this->http->FindPreg('/"desc":"(NO\s+FARE\s+FOUND\s+FOR\s+REQUESTED\s+ITINERARY)"/m');

                    if (!$warningMsg1 && (empty($routes) || empty($warningMsg) || isset($unknownErrorFromTapAlliance))) {
                        if (empty($routes)) {
                            if (strpos($this->http->Response['body'], '"desc":"Transaction unable to process') !== false) {
                                throw new CheckRetryNeededException(5, 0);
                            }
                            $this->sendNotification('check error (2) // ZM');

                            throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                        }
                        $this->sendNotification('check error (3) // ZM');
                    } elseif ($warningMsg1 && empty($warningMsg)) {
                        $warningMsg = $warningMsg1;
                    }
                } elseif (empty($routes)) {
                    if ($this->http->Response['code'] == 403) {
                        throw new CheckRetryNeededException(5, 0);
                    }

                    throw new \CheckException("Something went wrong", ACCOUNT_ENGINE_ERROR);
                }
            } elseif (empty($data->data->offers)) {
                $warningMsg = 'Select another date with available flights';
            } else {
                $routesOnlyTap = $this->parseRewardFlights($data, $fields, false);

                if (!empty($routesOnlyTap) && $routesOnlyTap != $routes) {
                    $allRoutes = array_merge($routesOnlyTap, $routes);
                    $routes = array_map('unserialize', array_unique(array_map('serialize', $allRoutes)));
                }
            }
        }

        if (isset($this->changedCabin) && count($this->changedCabin) === 2 && isset($this->tapRoute, $this->starAllianceRoute) && $this->tapRoute && $this->starAllianceRoute && empty($warningMsg)) {
            $this->logger->notice('possible duplicates with different cabins'); // так-то руками не находились, но на всякий влог отметка
        }

        if (empty($routes) && !empty($warningMsg)) {
            if ($warningMsg === "Read timed out") {
                throw new \CheckException($warningMsg, ACCOUNT_PROVIDER_ERROR);
            }

            if ($warningMsg === "Bad value (coded) - timeDetails") {
                $warningMsg = 'Select another date with available flights';
            }
            $this->SetWarning($warningMsg);
        }

        return ['routes' => $routes];
    }

    private function getCabinNew(string $cabin): string
    {
        $cabins = [
            'economy'   => 'economy',
            'executive' => 'business',
        ];

        if (isset($cabins[$cabin])) {
            return $cabins[$cabin];
        }
        $this->sendNotification("RA check cabin {$cabin} // MI");

        throw new \CheckException("check cabin {$cabin}", ACCOUNT_ENGINE_ERROR);
    }

    private function otherTypeSearch($fields, $starAlliance = false)
    {
        $this->logger->notice(__METHOD__);

        $dateStr = date('dmY', $fields['DepDate']);
        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'Authorization' => 'Bearer ' . $this->sessionToken,
            'Content-Type'  => 'application/json',
            'Origin'        => 'https://booking.flytap.com',
            'Referer'       => 'https://booking.flytap.com/booking/flights',
        ];
        $payload = '{"adt":' .
            $fields['Adults'] . ',"airlineId":"TP","c14":0,"cabinClass":"E","chd":0,"departureDate":["' . $dateStr . '"],"destination":["' . $fields['ArrCode'] . '"],"inf":0,"language":"en-us","market":"US","origin":["' . $fields['DepCode'] . '"],"passengers":{"ADT":' . $fields['Adults'] . ',"YTH":0,"CHD":0,"INF":0},"returnDate":"' . $dateStr . '","tripType":"O","validTripType":true,"payWithMiles":true,"starAlliance":' . var_export($starAlliance, true) . ',"yth":0}';
        $this->http->RetryCount = 0;
        $this->http->PostURL("https://booking.flytap.com/bfm/rest/booking/availability/search?payWithMiles=true&starAlliance=" . var_export($starAlliance, true), $payload, $headers, 30);
        $this->http->RetryCount = 2;

        if ($this->http->Response['code'] == 503
            || $this->http->FindPreg('/"status":"(500)"/')
            || strpos($this->http->Error, 'Network error 28 - Operation timed out after ') !== false
            || strpos($this->http->Error, 'Network error 28 - Connection timed out after ') !== false
        ) {
            sleep(5);
            $this->http->RetryCount = 0;
            $this->http->PostURL("https://booking.flytap.com/bfm/rest/booking/availability/search?payWithMiles=true&starAlliance=" . var_export($starAlliance, true), $payload, $headers, 30);
            $this->http->RetryCount = 2;
        }

        if (strpos($this->http->Error, 'Network error 56 - Received HTTP code 407 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT') !== false
            || strpos($this->http->Error, 'Network error 28 - Operation timed out after ') !== false
            || strpos($this->http->Error, 'Network error 28 - Connection timed out after ') !== false
            || $this->http->Response['code'] == 403
            || strpos($this->http->Response['body'], '"desc":"Server is busy, please try again in a few minutes') !== false
            || strpos($this->http->Response['body'], '"desc":"Read timed out') !== false
        ) {
            throw new CheckRetryNeededException(5, 0);
        }

        if ($this->http->Response['code'] != 200) {
            throw new \CheckRetryNeededException(5, 0);
        }

        return $this->http->JsonLog();
    }

    private function parseRewardFlights($data, $fields = [], $starAlliance = true): array
    {
        $this->logger->notice(__METHOD__);
        $routes = [];

        foreach ($data->data->listOutbound as $outbound) {
            foreach ($outbound->relateOffer as $keyRoute => $rateOffer) {
                $offer = null;

                foreach ($data->data->offers->listOffers as $itemOffer) {
                    if ($itemOffer->idOffer == $rateOffer) {
                        $offer = $itemOffer;

                        break;
                    }
                }

                if (empty($offer)) {
                    $this->logger->info('skip offer ' . $rateOffer . ' no data');

                    continue;
                }
                $fareFamily = $offer->outFareFamily;
                $route = [
                    'distance'  => null,
                    'num_stops' => $outbound->numberOfStops,
                    'times'     => [
                        'flight'  => $this->convertMinDuration($outbound->duration),
                        'layover' => null,
                    ],
                    'redemptions' => [
                        'miles'   => round($offer->outbound->totalPoints->price / $fields['Adults']),
                        'program' => $this->AccountFields['ProviderCode'],
                    ],
                    'payments' => [
                        'currency' => $data->data->offers->currency,
                        'taxes'    => round(($offer->outbound->totalPrice->tax + $offer->outbound->totalPrice->obFee) / $fields['Adults'], 2),
                        'fees'     => null,
                    ],
                    'connections'     => [],
                    'tickets'         => null,
                    'award_type'      => null,
                    'classOfService'  => $this->convertClassOfService($offer->outFareFamily),
                ];
                // Connections
                foreach ($outbound->listSegment as $keyConn => $segment) {
                    if (!empty($offer->outbound->cabin)) {
                        $cabin = $this->getCabinNew(strtolower($offer->outbound->cabin[$keyConn]));
                    } else {
                        $this->logger->info('skip offer ' . $rateOffer . ' no cabins, sold out');

                        break 2;
                    }
                    $rbd = $offer->outbound->rbd[$keyConn];

                    if (!$starAlliance && in_array($fareFamily, ['AWEXECU', 'AWEXENEW']) && !in_array($rbd, ['I', 'Z'])) {
                    } elseif ($outbound->numberOfStops > 0 && /*$starAlliance &&*/ in_array($fareFamily, ['AWEXECU', 'AWEXENEW', 'AWFIRST']) && $rbd != 'I') {
                        $this->logger->notice("Change $cabin for economy");

                        if ($cabin !== 'economy') {
                            if (isset($this->changedCabin)) {
                                $this->changedCabin[$starAlliance] = true;
                            } else {
                                $this->changedCabin = [$starAlliance => true];
                            }
                        }
                        $cabin = 'economy';
                    }

                    $route['connections'][] = [
                        'num_stops' => count($segment->technicalStops ?? []),
                        'departure' => [
                            'date'     => date('Y-m-d H:i', strtotime($segment->departureDate)),
                            'dateTime' => strtotime($segment->departureDate),
                            'airport'  => $segment->departureAirport,
                            'terminal' => $segment->departureTerminal,
                        ],
                        'arrival' => [
                            'date'     => date('Y-m-d H:i', strtotime($segment->arrivalDate)),
                            'dateTime' => strtotime($segment->arrivalDate),
                            'airport'  => $segment->arrivalAirport,
                            'terminal' => $segment->arrivalTerminal,
                        ],
                        'meal'       => null,
                        'cabin'      => $cabin,
                        'flight'     => ["{$segment->carrier}{$segment->flightNumber}"],
                        'airline'    => $segment->carrier,
                        'operator'   => $segment->operationCarrier,
                        'distance'   => null,
                        'aircraft'   => $segment->equipment,
                        'times'      => [
                            'flight'  => $this->convertMinDuration($segment->duration),
                            'layover' => $this->convertMinDuration($segment->stopTime),
                        ],
                    ];
                }
                $route['num_stops'] = count($route['connections']) - 1 + array_sum(array_column($route['connections'], 'num_stops'));
                $this->logger->debug(var_export($route, true), ['pre' => true]);
                $routes[] = $route;
            }
        }

        return $routes;
    }

    private function convertClassOfService(string $str): ?string
    {
        switch ($str) {
            case "AWBASIC":
            case "AWBASINT":
            case "AWCLANEW":
                return 'Economy';

            case "AWEXECU":
            case "AWEXEINT":
            case "AWEXENEW":
                return 'Business';
        }
        $this->sendNotification('check outFareFamily: ' . $str);

        return null;
    }

    private function convertMinDuration($minutes)
    {
        $format = gmdate('H:i', $minutes * 60);

        if ($format == '00:00') {
            return null;
        }

        return $format;
    }

    private function acceptCookie()
    {
        $accept = $this->waitForElement(\WebDriverBy::xpath('//button[@id="onetrust-accept-btn-handler"]'), 0);

        if ($accept) {
            $this->logger->debug("click accept");
            $accept->click();
            $this->waitFor(function () {
                return !$this->waitForElement(\WebDriverBy::xpath('//button[@id="onetrust-accept-btn-handler"]'), 0);
            }, 20);
        }
    }

    private function tryAjax(array $fields, string &$sessionToken, ?bool $alliance = true)
    {
        $this->logger->notice(__METHOD__);
        $dateStr = date('dmY', $fields['DepDate']);
        $dateStrCheck = date('Y-m-d', $fields['DepDate']);

        if (empty($sessionToken)) {
            return null;
        }
        try {
            if ($alliance) {
                $this->logger->info('tap+alliance');
                sleep(2);
            } else {
                $this->logger->info('tap only');
            }
            $this->driver->executeScript('localStorage.removeItem("tapResponseAjax");');

            $tt = $this->getRequestScript($fields, $dateStr, $sessionToken, $alliance);
            $this->logger->debug($tt, ['pre' => true]);

            $returnData = $this->driver->executeScript($tt);
        } catch (\Facebook\WebDriver\Exception\ScriptTimeoutException | \WebDriverException $e) {
            $this->logger->error('[ScriptTimeoutException]: ' . $e->getMessage());
            $this->driver->executeScript('window.stop();');
            sleep(2);
            $returnData = $this->driver->executeScript($tt);
        } catch (\Facebook\WebDriver\Exception\JavascriptErrorException  $e) {
            $this->logger->error('[JavascriptErrorException]: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\Facebook\WebDriver\Exception\WebDriverCurlException | \Facebook\WebDriver\Exception\WebDriverException  $e) {
            $this->logger->error('[WebDriverCurlException]: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        $res = $this->http->JsonLog($returnData);

        if (!$res) {
            $this->logger->debug($returnData, ['pre' => true]);

            if (strpos($returnData, '/session/') !== false) {
                $this->logger->warning('selenium failed');

                throw new \CheckRetryNeededException(5, 0);
            }

            if (empty($this->bodyResponseOnlyTap) && $alliance) {
                $this->logger->error("no data, restart");

                throw new \CheckRetryNeededException(5, 0);
            }
            $this->logger->error("no data");

            return null;
        }

        if (strpos($returnData, '"desc":"Invalid FlightSearch data') !== false) {
            // Flights may be. Retry does not help at all, only a full restart
            throw new CheckRetryNeededException(5, 10);
        }

        $memReturnData = null;

        if (empty($returnData)
            || (
                strpos($returnData, '"errors":[{"code":') === false
                && strpos($returnData, 'departureDate":"' . $dateStrCheck) === false
            )
            || strpos($returnData, '"desc":"Read timed out') !== false
            || strpos($returnData, '"desc":"Past date/time not allowed"') !== false
            || strpos($returnData, '"desc":"Bad value (coded) - timeDetails"') !== false
            || strpos($returnData, '"code":"500","type":"ERROR"') !== false
            || strpos($returnData, '"desc":"11|Session|"') !== false
            || strpos($returnData, '"errors":[{"code":"931","type":"ERROR"') !== false // Extremely rare, but it happens to be a false error. flights actually exist, but it says that it was not found
            || strpos($returnData, '"desc":"Server is busy, please try again in a few minutes') !== false
            || $this->http->FindPregAll('/<body>Bad Request<\/body>/', $returnData, PREG_PATTERN_ORDER, false, false)
            || strpos($returnData, '"desc":"42|Application|Too many opened conversations. Please close them and try again') !== false
            // Transaction unable to process : TECH INIT   ||   Transaction unable to process : AVL
            || strpos($returnData, '"desc":"Transaction unable to process') !== false
        ) {
            $script = "return sessionStorage.getItem('token');";
            $this->logger->debug("[run script]");
            $this->logger->debug($script, ['pre' => true]);
            $sessionToken = $this->driver->executeScript($script);
            $sessionToken = trim($sessionToken, '"');
            $this->logger->debug('token ' . $sessionToken);

            if (strpos($sessionToken, '/session/') !== false) {
                $this->logger->warning('selenium failed');

                throw new \CheckRetryNeededException(5, 0);
            }

            $tt = $this->getRequestScript($fields, $dateStr, $sessionToken, $alliance);

            if (strpos($returnData, '"errors":[{"code":"931","type":"ERROR"') !== false
                || strpos($returnData, '"desc":"Server is busy, please try again in a few minutes') !== false
            ) {
                $this->logger->debug("set mem returnData");
                $memReturnData = $returnData; // Because it is not always a false answer
            }
            // helped
            try {
                $this->logger->debug($tt, ['pre' => true]);
                $returnData = $this->driver->executeScript($tt);
                $this->logger->debug("new returnData");
                $res = $this->http->JsonLog($returnData);
            } catch (\Facebook\WebDriver\Exception\JavascriptErrorException $e) {
                $this->logger->error('JavascriptErrorException: ' .  $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            } catch (\Facebook\WebDriver\Exception\WebDriverException
            | \Facebook\WebDriver\Exception\WebDriverCurlException $e) {
                $this->logger->error('JavascriptErrorException: ' .  $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }
        }

        if (empty($returnData)) {
            $this->logger->error('No has Data');

            throw new CheckRetryNeededException(5, 10);
        }

        if (!empty($returnData)
            && strpos($returnData, '"errors":[{"code":') === false
            && strpos($returnData, 'departureDate":"' . $dateStrCheck) === false
        ) {
            $this->logger->error('wrong response/departureDate');

            throw new CheckRetryNeededException(5, 10);
        }

        if ((strpos($returnData, '"code":"500","type":"ERROR"') !== false
                || strpos($returnData, '"status":"400","errors"') !== false)
            && isset($memReturnData)
        ) {
            $this->logger->debug("get mem returnData");
            $returnData = $memReturnData;
        }

        if (
            (strpos($returnData, 'Bad Request') !== false
                && $this->http->FindPregAll('/<body>Bad Request<\/body>/', $returnData, PREG_PATTERN_ORDER, false, false))
            || (strpos($returnData, '11|Session|') !== false
                && $this->http->FindPreg('/"desc":"\s*11\|Session\|"/', false, $returnData))
            || strpos($returnData, '"desc":"Server is busy, please try again in a few minutes') !== false
            || strpos($returnData, '"desc":"Invalid FlightSearch data') !== false
        ) {
            throw new CheckRetryNeededException(5, 0);
        }

        if ($alliance) {
            $this->bodyResponseAlliance = $returnData;
        } else {
            $this->bodyResponseOnlyTap = $returnData;
        }

        return $res;
    }

    private function getRequestScript(array $fields, string $dateStr, string $sessionToken, bool $alliance)
    {
        return '
                    var xhttp = new XMLHttpRequest();
                    xhttp.withCredentials = true;
                    xhttp.open("POST", "https://booking.flytap.com/bfm/rest/booking/availability/search?payWithMiles=true&starAlliance=' . var_export($alliance, true) . '", false);
                    xhttp.setRequestHeader("Content-type", "application/json");
                    xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
                    xhttp.setRequestHeader("Authorization", "Bearer ' . $sessionToken . '");
                    xhttp.setRequestHeader("Connection", "keep-alive");
                    xhttp.setRequestHeader("Accept-Encoding", "gzip, deflate, br");
                    xhttp.setRequestHeader("Origin", "https://booking.flytap.com");
                    xhttp.setRequestHeader("Sec-Fetch-Dest", "empty");
                    xhttp.setRequestHeader("Sec-Fetch-Mode", "cors");
                    xhttp.setRequestHeader("Sec-Fetch-Site", "same-origin");
                    xhttp.setRequestHeader("Referer", "https://booking.flytap.com/booking/flights");

        
                    var data = JSON.stringify({"adt":' . $fields['Adults'] . ',"airlineId":"TP","c14":0,"cabinClass":"B","chd":0,"departureDate":["' . $dateStr . '"],"destination":["' . $fields['ArrCode'] . '"],"inf":0,"language":"en-us","market":"US","origin":["' . $fields['DepCode'] . '"],"passengers":{"ADT":' . $fields['Adults'] . ',"YTH":0,"CHD":0,"INF":0},"returnDate":"' . $dateStr . '","tripType":"O","validTripType":true,"payWithMiles":true,"starAlliance":' . var_export($alliance, true) . ',"yth":0});
                    var responseText = null;
                    xhttp.onreadystatechange = function() {
                        responseStatus = this.status;
                        if (this.readyState == 4 && this.status == 200) {
                            responseText = this.responseText;
                            localStorage.setItem("tapResponseAjax",this.responseText);
                        }
                    };
                    xhttp.send(data);
                    return responseText;
        ';
    }

    private function checkRouteData($fields, $sessionToken): ?bool
    {
        $this->logger->notice(__METHOD__);

        if (empty($sessionToken)) {
            return null;
        }

        $origins = \Cache::getInstance()->get('ra_tapportugal_origins');

        if (!is_array($origins)) {
            $tt = '
                var xhttp = new XMLHttpRequest();
                xhttp.open("POST", "https://booking.flytap.com/bfm/rest/journey/origin/search", false);
                xhttp.setRequestHeader("Content-type", "application/json");
                xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
                xhttp.setRequestHeader("Authorization","Bearer ' . $sessionToken . '");
                xhttp.setRequestHeader("Origin","https://booking.flytap.com");
                xhttp.setRequestHeader("Referer","https://booking.flytap.com/booking/flights");
        
                var data = JSON.stringify({"tripType":"O","market":"US","language":"en-us","airlineIds":["TP"],"payWithMiles":true});
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        localStorage.setItem("retData",this.responseText);
                    }
                };
                xhttp.send(data);
                v = localStorage.getItem("retData");
                return v;
            ';
            $this->logger->debug($tt);

            try {
                $originData = $this->driver->executeScript($tt);
            } catch (\Facebook\WebDriver\Exception\InvalidSelectorException $e) {
                $this->logger->error('[InvalidSelectorException]: ' . $e->getMessage());
                $this->driver->executeScript('window.stop();');
                sleep(2);
                $originData = $this->driver->executeScript($tt);
            } catch (\Facebook\WebDriver\Exception\ScriptTimeoutException $e) {
                $this->logger->error('[ScriptTimeoutException]: ' . $e->getMessage());
                $this->driver->executeScript('window.stop();');
                sleep(2);
                $originData = $this->driver->executeScript($tt);
            } catch (\Facebook\WebDriver\Exception\WebDriverCurlException $e) {
                $this->logger->error('WebDriverCurlException: ' . $e->getMessage());

                throw new \CheckRetryNeededException(5, 0);
            }

            $data = $this->http->JsonLog($originData, 1, true);

            $origins = [];

            if (isset($data['data']['origins'])) {
                $origins = array_map(function ($d) {
                    return $d['airport'];
                }, $data['data']['origins']);

                if (!empty($origins)) {
                    \Cache::getInstance()->set('ra_tapportugal_origins', $origins, 24 * 60 * 60);
                }
            }
        }

        if (is_array($origins) && !empty($origins) && !in_array($fields['DepCode'], $origins)) {
            $this->SetWarning('No flights from ' . $fields['DepCode']);
            $this->noRoute = true;

            return false;
        }

        $tt = '
            var xhttp = new XMLHttpRequest();
            xhttp.open("POST", "https://booking.flytap.com/bfm/rest/journey/destination/search", false);
            xhttp.setRequestHeader("Content-type", "application/json");
            xhttp.setRequestHeader("Accept", "application/json, text/plain, */*");
            xhttp.setRequestHeader("Authorization","Bearer ' . $sessionToken . '");
            xhttp.setRequestHeader("Origin","https://booking.flytap.com");
            xhttp.setRequestHeader("Referer","https://booking.flytap.com/booking/flights");
        
            var data = JSON.stringify({"tripType":"O","market":"US","language":"en-us","airlineIds":["TP"],"payWithMiles":true,"origin":"' . $fields['DepCode'] . '"});
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    localStorage.setItem("retData",this.responseText);
                }
            };
            xhttp.send(data);
            v = localStorage.getItem("retData");
            return v;
        ';
        $this->logger->debug($tt);

        try {
            $returnData = $this->driver->executeScript($tt);
        } catch (\Facebook\WebDriver\Exception\InvalidSelectorException $e) {
            $this->logger->error('[InvalidSelectorException]: ' . $e->getMessage());
            $this->driver->executeScript('window.stop();');
            sleep(2);
            $returnData = $this->driver->executeScript($tt);
        } catch (\Facebook\WebDriver\Exception\ScriptTimeoutException $e) {
            $this->logger->error('[ScriptTimeoutException]: ' . $e->getMessage());
            $this->driver->executeScript('window.stop();');
            sleep(2);
            $returnData = $this->driver->executeScript($tt);
        } catch (\Facebook\WebDriver\Exception\JavascriptErrorException $e) {
            $this->logger->error('UnexpectedJavascriptException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        } catch (\Facebook\WebDriver\Exception\WebDriverCurlException
        | \Facebook\WebDriver\Exception\WebDriverException $e) {
            $this->logger->error('WebDriverCurlException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        if (empty($returnData) || strpos('"code":"500","type":"ERROR"', $returnData) !== false) {
            sleep(2);
            // helped
            $returnData = $this->driver->executeScript($tt);

            if (empty($returnData)) {
                throw new \CheckRetryNeededException(5, 0);
            }

            if (strpos('"code":"500","type":"ERROR"', $returnData) !== false) {
                $returnData = null;
            }
        }
        $data = $this->http->JsonLog($returnData, 1, true);
        $noFlight = true;
        $flight = null;

        if (isset($data['data']['destinations'])) {
            foreach ($data['data']['destinations'] as $destination) {
                if ($destination['airport'] === $fields['ArrCode']) {
                    $flight = $destination;
                    $noFlight = false;
                    $this->tapRoute = $destination['tapRoute'];
                    $this->starAllianceRoute = $destination['starAllianceRoute'];

                    break;
                }
            }

            if ($noFlight) {
                $this->SetWarning('No flights from ' . $fields['DepCode'] . ' to ' . $fields['ArrCode']);
                $this->noRoute = true;

                return false;
            }

            if ($flight && array_key_exists('tapRoute', $flight)) {
                return $flight['tapRoute'];
            }
        }

        return true;
    }

    private function isBadProxy()
    {
        return $this->http->FindSingleNode("//h1[contains(., 'This site can’t be reached')]")
            || $this->http->FindSingleNode("//h1[normalize-space()='Access Denied']")
            || $this->http->FindSingleNode("//span[contains(text(), 'This site can’t be reached')]")
            || $this->http->FindSingleNode("//span[contains(text(), 'This page isn’t working')]")
            || $this->http->FindSingleNode("//p[contains(text(), 'There is something wrong with the proxy server, or the address is incorrect.')]");
    }

    private function getToken()
    {
        $script = "return sessionStorage.getItem('token');";
        $this->logger->debug("[run script]");
        $this->logger->debug($script, ['pre' => true]);
        $sessionToken = $this->driver->executeScript($script);
        $sessionToken = trim($sessionToken, '"');
        $this->logger->debug('token ' . $sessionToken);

        if (strpos($sessionToken, '/session/') !== false) {
            $this->logger->warning('selenium failed');

            throw new \CheckRetryNeededException(5, 0);
        }

        return $sessionToken;
    }

    private function parseCalendarJson($calendar, $fields)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info("Parse Result Calendar", ['Header' => 3]);
        $result = [];

        if (empty($calendar->data->outPanel)) {
            $this->logger->warning("No has data");

            throw new \CheckRetryNeededException(5, 0);
        }

        foreach ($calendar->data->outPanel->listTab as $day) {
            if ($day->available != 1) {
                continue;
            }

            $dateTime = \DateTime::createFromFormat('dmY', $day->date);

            $result[] = [
                'date'        => $dateTime->format('Y-m-d'),
                'redemptions' => ['miles' => round($day->totalPoints->price / $fields['Adults'])],
                'payments'    => [
                    'currency' => $calendar->data->outPanel->currency,
                    'taxes'    => round(($day->totalPrice->tax + $day->totalPrice->obFee) / $fields['Adults'], 2),
                    'fees'     => null,
                ],
                'cabin'        => null,
                'brandedCabin' => null,
            ];
        }

        $this->logger->debug(var_export($result, true), ['pre' => true]);

        if (empty($result)) {
            $this->SetWarning('There are no flights for the this month');
        }

        return $result;
    }
}
