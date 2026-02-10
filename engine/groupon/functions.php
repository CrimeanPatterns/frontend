<?php

use AwardWallet\Common\Parsing\WrappedProxyClient;
use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;
use AwardWallet\Common\Parsing\Html;

require_once "GrouponAbstract.php";

class TAccountCheckerGroupon extends TAccountChecker
{
    use ProxyList;
    use SeleniumCheckerHelper;

    public $facebook;
    protected $GrouponHandler;

    public $mainBalance = 0;

    public static function FormatBalance($fields, $properties)
    {
        if ($fields['Login2'] == 'UK') {
            return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "£%0.2f");
        }

        return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "$%0.2f");
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        //$this->ShowLogs = true;
        switch ($this->AccountFields['Login2']) {
            case "UK":
                require_once "GrouponUSANew.php";
                $this->GrouponHandler = new GrouponUSANew($this);
                $this->GrouponHandler->urlProvider = 'www.groupon.co.uk';
                $this->GrouponHandler->baseDomainCookie = '.groupon.co.uk';
            break;

            case 'Australia':
                if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
                    throw new CheckException("Incorrect login. Try again or reset your password.", ACCOUNT_INVALID_PASSWORD);
                }

                require_once "GrouponUSANew.php";
                $this->GrouponHandler = new GrouponUSANew($this);
                $this->GrouponHandler->urlProvider = 'www.groupon.com.au';
                $this->GrouponHandler->baseDomainCookie = '.groupon.com.au';

                // $this->GrouponHandler->app_id = '139243329469945';
                break;

            case "Canada":
                // require_once "GrouponUK.php";
                // $this->GrouponHandler = new GrouponUK($this); # like UK
                require_once "GrouponUSANew.php";
                $this->GrouponHandler = new GrouponUSANew($this);

                $this->GrouponHandler->urlProvider = 'www.groupon.ca';

                $this->GrouponHandler->app_id = '122332024501835';
                $this->GrouponHandler->startCookies = [
                    [
                        "name"    => "user_locale",
                        "path"    => "/",
                        "domain"  => ".groupon.ca",
                        "expires" => "Wed, 18-11-2019 10:57:52 GMT",
                        "value"   => "en_CA",
                    ],
                ];

            break;

            case "USA":
            default:
                // Please enter a valid email address.
                if (filter_var($this->AccountFields['Login'], FILTER_VALIDATE_EMAIL) === false) {
                    throw new CheckException("Please enter a correct email address.", ACCOUNT_INVALID_PASSWORD);
                }

                require_once "GrouponUSANew.php";
                $this->GrouponHandler = new GrouponUSANew($this);
                $this->GrouponHandler->startCookies = [
                    [
                        "name"   => "division",
                        "path"   => "/",
                        "domain" => ".groupon.com",
                        "value"  => "chicago",
                    ],
                    [
                        "name"   => "user_locale",
                        "path"   => "/",
                        "domain" => ".groupon.com",
                        "value"  => "en_US",
                    ],
                ];

            break;
        }
        $this->GrouponHandler->setCredentials($this->AccountFields['Login'], $this->AccountFields['Pass']);

        if (isset($this->AccountFields['AccountID'])) {
            $this->GrouponHandler->setAccountID(ArrayVal($this->AccountFields, 'RequestAccountID', $this->AccountFields['AccountID']));
        }

        if (isset($this->AccountFields['Login3'])) {
            $this->GrouponHandler->setLoginType($this->AccountFields['Login3']);
        }

        return $this->GrouponHandler->LoadLoginForm();
    }

    public function parseCaptcha($key = null, $pageurl = null)
    {
        $this->logger->debug(__METHOD__);
        $key = $key ?? $this->http->FindSingleNode("//div[@class = 'g-recaptcha']/@data-sitekey");

        if (!$key) {
            return false;
        }

        $recognizer = $this->getCaptchaRecognizer();
        $recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $pageurl ?? $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($recognizer, $key, $parameters);
    }

    public function selenium()
    {
        $this->logger->notice(__METHOD__);
        $logout = false;
        $retry = false;
        $checker = clone $this;
        $this->http->brotherBrowser($checker->http);

        try {
            $this->logger->notice("Running Selenium...");
            $checker->UseSelenium();

            $checker->useChromePuppeteer();

            $request = FingerprintRequest::chrome();
            $request->browserVersionMin = HttpBrowser::BROWSER_VERSION_MIN;
            $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);

            if ($fingerprint !== null) {
                $checker->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
                $checker->http->setUserAgent($fingerprint->getUseragent());
            }

            $checker->http->SetProxy($this->proxyReCaptcha());
            /*
            $wrappedProxy = $this->services->get(WrappedProxyClient::class);
            $proxy = $wrappedProxy->createPort($checker->http->getProxyParams());
            $checker->seleniumOptions->antiCaptchaProxyParams = $proxy;
            */
            $checker->seleniumOptions->antiCaptchaProxyParams = $checker->getCaptchaProxy();
            $checker->seleniumOptions->addAntiCaptchaExtension = true;

            $checker->disableImages();
            $checker->useCache();
            $checker->http->saveScreenshots = true;
            $checker->http->start();

            try {
                $checker->http->getURL('https://' . $this->GrouponHandler->urlProvider . '/login');
            } catch (Facebook\WebDriver\Exception\TimeoutException | TimeoutException $e) {
                $this->logger->error("Exception: " . (strlen($e->getMessage()) > 40 ? substr($e->getMessage(), 0, 37) . '...' : $e->getMessage()));
                $checker->driver->executeScript('window.stop();');
            }
            $checker->Start();

            $form = '(//form[@data-bhw = "LoginForm"] | //div[@data-bhw="ls-signin-form"]/form)';
            $loginInput = $checker->waitForElement(WebDriverBy::xpath("{$form}//input[@id = 'login-email-input' or @placeholder=\"Email\"]"), 10);
            $delay = 0;

            if ($button = $checker->waitForElement(WebDriverBy::xpath("{$form}//button[contains(., 'Continue')]"), 0)) {
                $loginInput->sendKeys($this->AccountFields['Login']);

                $checker->waitFor(function () use ($checker) {
                    $this->logger->warning("Solving is in process...");
                    sleep(3);
                    $this->savePageToLogs($checker);

                    return !$this->http->FindSingleNode('//a[contains(text(), "Solving is in process...")]');
                }, 200);

                $this->savePageToLogs($checker);
                $this->logger->debug("click Continue");
                $button->click();
                $delay = 20;
            }

            $passwordInput = $checker->waitForElement(WebDriverBy::xpath("{$form}//input[@id = 'login-password-input' or @placeholder=\"Password\"]"), $delay);
            $button = $checker->waitForElement(WebDriverBy::xpath("{$form}//*[@id = 'signin-button' or @data-bhw=\"signin-button\"]"), 0);

            if (!$loginInput || !$passwordInput || !$button) {
                $this->logger->error("something went wrong");
                // save page to logs
                $this->savePageToLogs($checker);
                // Access Denied
                // Oops! Internal Error
                if ($this->http->FindSingleNode('
                        //*[self::h1 or self::span][contains(text(), "Access Denied")]
                        | //h2[contains(text(), "Oops! Internal Error")]
                    ')
                ) {
                    $retry = true;
                }
                /**
                 * Groupon is temporarily unavailable.
                 *
                 * Either because we're updating the site or because someone spilled coffee on it again.
                 * We'll be back just as soon as we finish the update or clean up the coffee.
                 *
                 * Thanks for your patience.
                 */
                if ($message = $this->http->FindSingleNode('//p[contains(text(), "Either because we\'re updating the site or because someone spilled coffee on it again.")] | //span[contains(text(), "Response not successful:")]')
                ) {
                    throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
                }

                return false;
            }

            $mover = new MouseMover($checker->driver);
            $mover->logger = $this->logger;
            $mover->duration = rand(300, 1000);
            $mover->steps = rand(10, 20);

            if ($loginInput = $checker->waitForElement(WebDriverBy::xpath("{$form}//input[@id = 'login-email-input' or @placeholder=\"Email\"]"), 0)) {
                try {
                    $mover->moveToElement($loginInput);
                    $mover->click();
                } catch (Facebook\WebDriver\Exception\MoveTargetOutOfBoundsException | MoveTargetOutOfBoundsException $e) {
                    $this->logger->error('[MoveTargetOutOfBoundsException]: ' . $e->getMessage(), ['pre' => true]);
                }

                try {
                    $loginInput->clear();
                } catch (Facebook\WebDriver\Exception\InvalidElementStateException | InvalidElementStateException $e) {
                    $this->logger->error('[MoveTargetOutOfBoundsException]: ' . $e->getMessage(), ['pre' => true]);
                    $loginInput = $checker->waitForElement(WebDriverBy::xpath("{$form}//input[@id = 'login-email-input' or @placeholder=\"Email\"]"), 10);
                    $this->savePageToLogs($checker);
                }

                $mover->sendKeys($loginInput, $this->AccountFields['Login'], 7);
    //            $loginInput->sendKeys($this->AccountFields['Login']);
            }

            try {
                $mover->moveToElement($passwordInput);
                $mover->click();
            } catch (Facebook\WebDriver\Exception\MoveTargetOutOfBoundsException | MoveTargetOutOfBoundsException $e) {
                $this->logger->error('[MoveTargetOutOfBoundsException]: ' . $e->getMessage(), ['pre' => true]);
            }

            $passwordInput->clear();
            $mover->sendKeys($passwordInput, $this->AccountFields['Pass'], 7);
//            $passwordInput->sendKeys($this->AccountFields['Pass']);

            $this->logger->debug("click accept");

            if ($accept = $checker->waitForElement(WebDriverBy::xpath("//*[@id = 'gdpr-accept']"), 0)) {
                $accept->click();
                $this->savePageToLogs($checker);
            }

            $this->logger->debug("click btn");
            $checker->driver->executeScript('
                document.querySelector(\'#signin-button, button[data-bhw="signin-button"]\').click();
            ');
//            $button->click();
            sleep(5);

            $this->logger->debug("wait result");
            $loginStatus = $checker->waitForElement(WebDriverBy::xpath("//a[contains(text(), 'Sign Out')] | //a[contains(text(), 'Logout')] | //button[@data-bhw='UserSignOut'] | //button[@data-bhw-path=\"Header|signin-btn\"]"), 5, false);
            // save page to logs
            $this->savePageToLogs($checker);

            if ($checker->waitForElement(WebDriverBy::xpath('//*[@id = "sec-overlay" or @id = "sec-text-if"]'), 0)) {
                $checker->waitFor(function () use ($checker) {
                    return !$checker->waitForElement(WebDriverBy::xpath('//*[@id = "sec-overlay" or @id = "sec-text-if"]'), 0);
                }, 120);
                $this->savePageToLogs($checker);
            }

            $checker->waitFor(function () use ($checker) {
                $this->logger->warning("Solving is in process...");
                sleep(3);
                $this->savePageToLogs($checker);

                return !$this->http->FindSingleNode('//a[contains(text(), "Solving is in process...")]');
            }, 200);

            $this->logger->debug("wait result");
            $loginStatus = $checker->waitForElement(WebDriverBy::xpath("//a[contains(text(), 'Sign Out')] | //a[contains(text(), 'Logout')] | //button[@data-bhw='UserSignOut'] | //button[@data-bhw-path=\"Header|signin-btn\" and not(contains(., 'Sign In'))]"), 5, false);
            // save page to logs
            $this->savePageToLogs($checker);

            if ($key = $this->http->FindSingleNode("//div[@id = 'login-recaptcha' or contains(@class, 'recaptchaBox')]//iframe[@title = 'reCAPTCHA']/@src", null, true, "/&k=([^&;]+)/")) {
                $this->DebugInfo = 'reCAPTCHA checkbox';
                $captcha = $this->parseCaptcha($key, $checker->http->currentUrl());

                if ($captcha === false) {
                    return false;
                }
                $checker->driver->executeScript('document.getElementById("g-recaptcha-response").value = "' . $captcha . '";');

                $passwordInput = $checker->waitForElement(WebDriverBy::xpath("{$form}//input[@id = 'login-password-input']"), 0);
                $this->savePageToLogs($checker);
                $passwordInput->clear();
                $mover->sendKeys($passwordInput, $this->AccountFields['Pass'], 7);

                $this->logger->debug("click btn");
                $checker->driver->executeScript('
                    document.getElementById(\'signin-button\').click();
                ');

                sleep(5);
            }

            $this->logger->debug("wait result");
            $loginStatus = $checker->waitForElement(WebDriverBy::xpath("//a[contains(text(), 'Sign Out')] | //a[contains(text(), 'Logout')] | //button[@data-bhw='UserSignOut'] | //button[@data-bhw-path=\"Header|signin-btn\" and not(contains(., 'Sign In'))]"), 5, false);
            // save page to logs
            $this->savePageToLogs($checker);

            if ($loginStatus) {
                $logout = true;
            } elseif (
                ($error = $checker->waitForElement(WebDriverBy::xpath("//div[contains(@class, 'error notification') and normalize-space(text()) != '']"), 0))
                || ($error = $checker->waitForElement(WebDriverBy::xpath("
                    //*[contains(@class,'generic-error') and normalize-space(text()) != '']
                    | //p[@id = 'error-login-email-input' and contains(@class, 'active')]
                    | //span[contains(@class, 'font-bold text-danger')]
                    | //h2[contains(text(), 'Oops! Internal Error')]
                    | //h1[contains(text(), 'Groupon is temporarily unavailable.')]
                    | //h1[contains(text(), 'Access Denied')]
                "), 0, false))
            ) {
                $message = $error->getText();
                $this->logger->error("[Error]: {$message}");

                if (stripos($message, 'Your username or password is incorrect') !== false) {
                    throw new CheckException('Your username or password is incorrect', ACCOUNT_INVALID_PASSWORD);
                }

                if (
                    stripos($message, 'Please enter a correct email address.') !== false
                    || stripos($message, 'The email or password did not match our records. Please try again.') !== false
                    || stristr($message, 'Oops! The email or password did not match our records. Please try again.')
                ) {
                    throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
                }

                if (stripos($message, 'Your password has expired,') !== false) {
                    throw new CheckException('Your password has expired, Use Forget Password Link to reset your password.', ACCOUNT_INVALID_PASSWORD);
                }

                // Access Denied
                // Oops! Internal Error
                if ($this->http->FindSingleNode('
                        //h1[contains(text(), "Access Denied")]
                        | //h2[contains(text(), "Oops! Internal Error")]
                    ')
                    || $message == 'Access Denied'
                ) {
                    $retry = true;
                }

                if (
                    /*
                    stripos($message, 'Oops! Internal Error') !== false
                    ||
                    */
                    stripos($message, 'Something went wrong, please try again in a few minutes.') !== false
                    || stripos($message, 'Groupon is temporarily unavailable.') !== false
                ) {
                    throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
                }

                $this->DebugInfo = $message;

                if (stripos($message, 'Please make sure you have clicked on reCAPTCHA checkbox') !== false) {
                    $this->DebugInfo = 'reCAPTCHA checkbox2';
                }
            }

            // save page to logs
            $this->savePageToLogs($checker);

            $cookies = $checker->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
            }

//            $checker->GrouponHandler->ParseCouponsSelenium();
            $checker->ParseCouponsSelenium();
            return false;
        } catch (ScriptTimeoutException $e) {
            $this->logger->error("ScriptTimeoutException exception: " . $e->getMessage());
            // retries
            if (strstr($e->getMessage(), 'timeout: Timed out receiving message from renderer')) {
                $retry = true;
            }
        } catch (StaleElementReferenceException $e) {
            $this->logger->error("StaleElementReferenceException exception: " . $e->getMessage());
            // retries
            if (strstr($e->getMessage(), 'Element not found in the cache')
                || strstr($e->getMessage(), 'element is not attached to the page document')) {
                $retry = true;
            }
        } finally {
            if ($retry && $this->AccountFields['Login2'] == 'USA') {
                $checker->markProxyAsInvalid();
            }

            $checker->http->cleanup();

            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                $this->logger->debug("[attempt]: {$this->attempt}");

                throw new CheckRetryNeededException(3, 10);
            }
        }

        // AccountID: 2264862
        if ($this->http->FindSingleNode("//h1[contains(text(), 'Access Denied')]") && $this->AccountFields['Pass'] == '<sVg/OnLOaD=prompt(0)>') {
            throw new CheckException("Incorrect login. Try again or reset your password.", ACCOUNT_INVALID_PASSWORD);
        }
        // Access Denied
        if ($this->http->FindSingleNode("//h1[contains(text(), 'Access Denied')]")) {
            $this->DebugInfo = 'Access Denied';
        }

        return $logout;
    }

    public function Login()
    {
        $this->http->Log(__METHOD__);

        return $this->GrouponHandler->Login();
    }

    public function Parse()
    {
        return $this->GrouponHandler->Parse();
    }

    // TODO:
    public function MarkCoupon(array $ids)
    {
        return $this->GrouponHandler->MarkCoupon($ids);
    }

    public function ParseCoupons($onlyActive = false)
    {
        return $this->GrouponHandler->ParseCoupons($onlyActive);
    }

    public function TuneFormFields(&$arFields, $values = null)
    {
        parent::TuneFormFields($arFields);
        $arFields['Login2']['Options'] = [
            ""          => "Select country",
            "Australia" => "Australia", // http://www.groupon.com.au/
            "Canada"    => "Canada", 		// http://www.groupon.ca/
            "UK"        => "United Kingdom",	// http://www.groupon.co.uk/
            "USA"       => "USA", 			// http://www.groupon.com/
        ];
    }

    public function ParseSelenium()
    {
        $this->logger->info('ParseSelenium', ['Header' => 3]);
        $this->http->FilterHTML = false;
        $this->SetProperty("SubAccounts", $this->ParseCouponsSelenium());

        $this->SetBalance($this->mainBalance);
        $login2 = $this->AccountFields['Login2'];
        // Groupon Bucks
        if (isset($this->grouponBucks)) {
            $this->logger->info('Groupon Bucks', ['Header' => 3]);
            $this->AddSubAccount([
                "Code"        => "groupon{$login2}GrouponBucks",
                "DisplayName" => "Groupon Bucks",
                "Balance"     => $this->grouponBucks,
            ]);
        }

        // refs #
        $this->logger->info('Cash Back Earned', ['Header' => 3]);

        if ($login2 == 'USA') {
            $this->http->GetURL('https://www.groupon.com/mylinkeddeals');
            $this->saveResponse();
            // Cash Back Earned
            $this->SetProperty('CashBackEarned', $this->http->FindSingleNode('//div[@class = "total-reward-flash"]'));
        }

        $this->logger->info('Name', ['Header' => 3]);
        // Name
        $this->setMyAccountLink();

        if (isset($this->myAccountLink)) {
            $this->http->GetURL($this->myAccountLink);
            $this->saveResponse();
        }
        $name = trim(Html::cleanXMLValue(
            (
                $this->http->FindSingleNode("//input[@id = 'user_firstName']/@value")
                ?? $this->http->FindPreg('/,"firstName":"([^\"]+)",/')
            )
            . ' ' .
            (
                $this->checker->http->FindSingleNode("//input[@id = 'user_lastName']/@value")
                ?? $this->http->FindPreg('/,"lastName":"([^\"]+)"/')
            )
        ));
        $this->SetProperty('Name', beautifulName($name));
    }

    /*
     * Open page with all deals
     */
    public function getGrouponsPage()
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info("Open page with all deals");
        // $myGrouponsLink = $this->checker->http->FindSingleNode($this->xpathMyGrouponsLink);

        $login2 = $this->AccountFields['Login2'];

        switch ($login2) {
            case 'UK':
                $myGrouponsLink = 'https://www.groupon.co.uk/mygroupons?sort_by=expires_at_desc';

                break;

            case 'Australia':
                $myGrouponsLink = 'https://www.groupon.com.au/mygroupons?sort_by=expires_at_desc';

                break;

            case 'Canada':
                $myGrouponsLink = 'https://www.groupon.ca/mygroupons?sort_by=expires_at_desc';

                break;

            default: // USA
                $myGrouponsLink = 'https://www.groupon.com/mygroupons?sort_by=expires_at_desc';

                break;
        }

        $this->logger->notice("[Page]: My Groupons page with all deals");
        $this->http->NormalizeURL($myGrouponsLink);
        $this->http->GetURL($basePage = $myGrouponsLink);
        $this->saveResponse();

        return $basePage;
    }

    public function parseSingleCouponSelenium($couponNode)
    {
        $this->logger->notice(__METHOD__);
        $http2 = clone $this->http;

        $pdfUrl = $this->http->FindSingleNode('.//a[contains(@href, ".pdf")]/@href', $couponNode);
        // redeemed
        $redeemed = $this->http->FindSingleNode(
            './/span[@class = "status redeemed" and contains(text(), "Redeemed")]', $couponNode);

        if (!$pdfUrl || $redeemed) {
            return false;
        }
        $this->http->NormalizeURL($pdfUrl);

        $orderId = $this->http->FindSingleNode('./@data-groupon', $couponNode);
        $detailsUrl = $this->http->FindSingleNode('.//a[normalize-space(text())="View Details" or @data-testid="voucher-action-SEE_DETAILS"]/@href', $couponNode);

        if (empty($detailsUrl)) {
            return [];
        }

        $this->http->NormalizeURL($detailsUrl);

        $dealUrl = $this->http->FindSingleNode('.//a[contains(@href, "/deals/")]/@href', $couponNode);
        $this->http->NormalizeURL($dealUrl);

        $result = [];
        $result['Link'] = $pdfUrl;

        // parse details
        $http2->GetURL($detailsUrl);
        $result['Quantity'] = $http2->FindSingleNode('//td[contains(text(), "Number Ordered:")]/following-sibling::td[1]');
        $price = trim($http2->FindSingleNode('//td[contains(text(), "Unit Price:")]/following-sibling::td[1]'));
        $result['Currency'] = $http2->FindPreg('/([^\d.]+)/', false, $price);
        $result['Price'] = $http2->FindPreg('/([\d.]+)/', false, $price);
        $value = trim($http2->FindSingleNode('//div[contains(text(), "Groupon Value:")]/following-sibling::div[1]'));
        $result['Value'] = $http2->FindPreg('/([\d.]+)/', false, $value);
        $result['Balance'] = ($result['Value'] - $result['Price']) * $result['Quantity'];

        if ($result['Value'] > 0) {
            $result['Save'] = round(100 - (($result['Price'] * 100) / $result['Value']));
        }
        $result['ShortName'] = $http2->FindSingleNode('//div[contains(@class, "item-details-container")]/preceding-sibling::div[1]/div/h1');
        $result['Code'] = sprintf('groupon%s', $orderId);
        $expirationDate = $http2->FindSingleNode('//div[contains(text(), "Expires:")]/following-sibling::div[1]');

        if (preg_match('/,\s*(\w+\s+\d+,\s+\d{4}|\d+\s+\w+\s+\d{4})/i', $expirationDate, $m)) {
            $expirationDate = $m[1];
        } else {
            $expirationDate = null;
        }
        $result['ExpirationDate'] = strtotime($expirationDate);

        // parse deal
        $http2->GetURL($dealUrl);
        $result['DisplayName'] = $http2->FindSingleNode('//h1[@id = "deal-title"]');

        return $result;
    }

    public function parseCouponsPerOrderSelenium($orderNode)
    {
        $this->logger->notice(__METHOD__);
        $couponNodes = $this->http->XPath->query('.//div[contains(@class, "voucher") and @data-groupon] | //div[@data-testid="voucher-list-item"]', $orderNode);
        $result = [];

        foreach ($couponNodes as $couponNode) {
            if ($coupon = $this->parseSingleCouponSelenium($couponNode)) {
                $result[] = $coupon;
            }
        }

        return $result;
    }

    public function ParseCouponsSelenium($onlyActive = false)
    {
        $this->logger->notice(__METHOD__);
        $coupons = [];
        // Open page with all deals
        $basePage = $this->getGrouponsPage();
        $pages = $this->getUrlsPages();
        array_unshift($pages, $basePage);
        $this->logger->debug('Groupons Pages All:');
        $this->logger->debug(var_export($pages, true));

        // Get Groupon Bucks
        $this->waitForElement(WebDriverBy::xpath("//span[contains(@class, 'bucks-balance')]"), 5);
        $this->saveResponse();
        $this->grouponBucks = Html::cleanXMLValue($this->http->FindSingleNode("//span[contains(@class, 'bucks-balance')]"));

        $finished = false;

        foreach ($pages as $page) {
            if ($this->http->currentUrl() != $page) {
                $this->http->GetURL($page);
            }

            $this->waitForElement(WebDriverBy::xpath("//ul[contains(@class, 'orders')] | //div[@data-testid = 'voucher-list-item']"), 10);
            $this->saveResponse();

            if (!$this->http->FindSingleNode("//ul[contains(@class, 'orders')]")) {
                break;
            }

            $this->logger->debug("[Page: \"$page\"]");
            $orderNodes = $this->http->XPath->query("//ul[contains(@class, 'orders')]/li | //div[@data-testid = 'voucher-list-item']");

            $this->logger->debug("[Total found orders]: " . $orderNodes->length);

            foreach ($orderNodes as $orderNode) {
                if ($this->http->FindPreg('/Expired On/i', false, $orderNode->nodeValue)) {
                    $finished = true;

                    break;
                }

                if ($this->http->FindPreg('/Expires On/i', false, $orderNode->nodeValue)
                    // They do not have a combustion date, they should be collected without a balance.
                    || (
                        !$this->http->FindSingleNode(".//div[contains(@class, 'expires_at')]", $orderNode, true)
                        && $this->http->FindSingleNode('.//a[contains(@href, ".pdf")]/@href', $orderNode)
                    )) {
                    $coupons = array_merge($coupons, $this->parseCouponsPerOrderSelenium($orderNode));
                }
            }

            if ($finished) {
                break;
            }
        }

        // Main Balance
        foreach ($coupons as $coupon) {
            $this->mainBalance += $coupon['Balance'];
        }
        $this->logger->debug('Main balance: ' . $this->mainBalance);

        return $coupons;
    }

    public function getUrlsPages()
    {
        $this->logger->notice(__METHOD__);

        $this->waitForElement(WebDriverBy::xpath("//div[contains(@class, 'pagination-pages')]/a[position()>1]"), 10);
        $this->saveResponse();
        $pages = $this->http->FindNodes("//div[contains(@class, 'pagination-pages')]/a[position()>1]/@href");

        foreach ($pages as &$page) {
            $this->http->NormalizeURL($page);
        }

        return $pages;
    }

    protected function setMyAccountLink()
    {
        $this->logger->notice(__METHOD__);
        $login2 = $this->AccountFields['Login2'];

        switch ($login2) {
            case 'UK':
                $this->myAccountLink = 'https://www.groupon.co.uk/myaccount';

                break;

            case 'Australia':
                $this->myAccountLink = 'https://www.groupon.com.au/myaccount';

                break;

            case 'Canada':
                $this->myAccountLink = 'https://www.groupon.ca/myaccount';

                break;

            default: // USA
                $this->myAccountLink = 'https://www.groupon.com/myaccount';

                break;
        }
        $this->http->NormalizeURL($this->myAccountLink);
    }
}

/**
 * GrouponOtherCountries.
 */
class GrouponOtherCountries extends GrouponAbstract
{
    public function LoadLoginForm()
    {
    }

    public function Login()
    {
    }

    public function Parse()
    {
    }

    public function MarkCoupon(array $ids)
    {
    }

    public function ParseCoupons($onlyActive = false)
    {
    }

    public function getUrlsPages()
    {
    }
}
