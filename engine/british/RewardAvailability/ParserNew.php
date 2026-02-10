<?php

namespace AwardWallet\Engine\british\RewardAvailability;

use AwardWallet\Common\Parser\Util\EmailDateHelper;
use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Engine\ProxyList;

class ParserNew extends \TAccountCheckerBritish
{
    use \PriceTools;
    use ProxyList;
    use \SeleniumCheckerHelper;

    protected $seleniumURL;

    private $isHot = false;
    private $curl;
    private $link;
    private $depDateIn;
    private $ports = [];
    /**
     * @var \CaptchaRecognizer
     */
    private $recognizer;

    private $secret;
    private $inCabin;
    private $inDateDep;

    public static function getRASearchLinks(): array
    {
        return ['https://www.britishairways.com/travel/redeem/execclub/_gf/en_us?eId=106019&tab_selected=redeem&redemption_type=STD_RED'=>'search page'];
    }

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();

        $this->KeepState = false;

        $this->debugMode = $this->AccountFields['DebugState'] ?? false;
//        $this->http->setHttp2(true);

        $this->UseSelenium();
        $this->setProxyGoProxies();
//        $resolutions = [
//            [1152, 864],
//            [1280, 720],
//            [1280, 768],
//            [1280, 800],
//            [1360, 768],
//            [1366, 768],
//        ];
//        $chosenResolution = $resolutions[array_rand($resolutions)];
//        $this->logger->info('chosenResolution:');
//        $this->logger->info(var_export($chosenResolution, true));
//        $this->setScreenResolution($chosenResolution);

//        if ($this->attempt !== 1) {
//            $this->useGoogleChrome(\SeleniumFinderRequest::CHROME_95);
//            $this->seleniumOptions->addHideSeleniumExtension = false;
//        } else {
        $this->useChromeExtension();
        $this->seleniumOptions->addPuppeteerStealthExtension = false;
        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->userAgent = null;
//        }

//        $this->seleniumOptions->userAgent = null;
        $this->usePacFile(false);

//        $request = FingerprintRequest::chrome();
//        $request->platform = 'Linux x86_64';
//        $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN;
//        $fingerprint = $this->services->get(FingerprintFactory::class)->getOne([$request]);
//
//        if (isset($fingerprint)) {
//            $this->seleniumOptions->fingerprint = $fingerprint->getFingerprint();
//            $this->http->setUserAgent($fingerprint->getUseragent());
//        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 14_7_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.4 Safari/605.1.15');
//        }

//        $this->disableImages();
        $this->http->saveScreenshots = true;

        $this->seleniumRequest->setHotSessionPool(
            self::class . 'new',
            $this->AccountFields['ProviderCode'],
            $this->AccountFields['AccountKey']
        );
    }

    public function getRewardAvailabilitySettings()
    {
        return [
            'supportedCurrencies'      => ['USD'],
            'supportedDateFlexibility' => 0, // 1
            'defaultCurrency'          => 'USD',
        ];
    }

    public function IsLoggedIn()
    {
        if (strpos($this->http->currentUrl(), 'www.britishairways.com') !== false) {
            $this->logger->debug('is hot');

            if ($btn = $this->waitForElement(\WebDriverBy::xpath("//ba-button[contains(.,'Stay logged in')]"), 0)) {
                $btn->click();
                sleep(2);
            }
            $this->http->GetURL("https://www.britishairways.com/travel/redeem/execclub/public/en_us?eId=106019&tab_selected=redeem&redemption_type=STD_RED",
                [], 20);
            $this->saveResponse();

            if ($this->isBadProxy()) {
                throw new \CheckRetryNeededException(5, 0);
            }

            return true;
        }
        $this->http->GetURL("https://www.britishairways.com/travel/viewaccount/execclub/_gf/en_us", [], 20);

        if ($this->http->FindSingleNode("//div[@class='member-name']")) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        if ($this->debugMode) {
            $this->secret = $this->Answers['secretCode'];
        }

        if ($this->attempt > 0) {
            if (strpos($this->http->currentUrl(), 'www.britishairways.com') !== false) {
                $this->logger->debug('is hot');

                if ($btn = $this->waitForElement(\WebDriverBy::xpath("//ba-button[contains(.,'Stay logged in')]"), 0)) {
                    $btn->click();
                    sleep(2);
                }
                $this->http->GetURL("https://www.britishairways.com/travel/redeem/execclub/public/en_us?eId=106019&tab_selected=redeem&redemption_type=STD_RED",
                    [], 20);
                $this->saveResponse();

                if ($this->isBadProxy()) {
                    throw new \CheckRetryNeededException(5, 0);
                }

                $this->isHot = true;

                return true;
            }
        }

        $this->Answers = [];
        $this->http->removeCookies();
        $this->logger->debug('[Parse start date: ' . date('Y/m/d H:i:s') . ']');
        $countryCode = $this->getCountryCode();

        // AccountID: 308446
        if (strstr($this->AccountFields['Pass'], '❺❽❼❽❽❻❷❻')) {
            throw new CheckException("The username and password do not match what is held on our system", ACCOUNT_INVALID_PASSWORD);
        }

        $this->driver->manage()->window()->maximize();
        $this->http->GetURL('https://www.britishairways.com/travel/loginr/public/en_' . $countryCode, [], 30);

        // fix Ireland region
        if ($countryCode == 'ie'
            && $this->http->currentUrl() == 'https://www.britishairways.com/en-gb/traveltrade') {
            $this->logger->notice('fix Ireland region');
            $this->http->GetURL('https://www.britishairways.com/travel/loginr/public/en_gb');
        }

        $loginInput = $this->waitForElement(\WebDriverBy::xpath('//input[@id = "username"]'), 7);

        if (
            !$loginInput
            && ($agreeBtn = $this->waitForElement(\WebDriverBy::xpath('//button[contains(text(), "Agree to all cookies") or @aria-label="Accept all cookies" or @id = "ensAcceptAll"]'), 0))
        ) {
            $this->saveResponse();
            $agreeBtn->click();
            $loginInput = $this->waitForElement(\WebDriverBy::xpath('//input[@id = "username"]'), 7);
        }

        if (
            !$loginInput
            && ($headerLoginBtn = $this->waitForElement(\WebDriverBy::xpath('//span[contains(text(), "Log in")]'), 3))
        ) {
            $this->saveResponse();
            $headerLoginBtn->click();
            $loginInput = $this->waitForElement(\WebDriverBy::xpath('//input[@id = "username"]'), 7);
        }

        // login
        if (!$loginInput) {
            $this->logger->error("something went wrong");
            $this->saveResponse();
            // retries
            if ($this->waitForElement(\WebDriverBy::xpath("//p[contains(text(), 'Unfortunately access to the web page you were trying to visit has been blocked as our systems have detected unusual traffic from your computer network.')]"), 0)) {
                $this->DebugInfo = "Request has been blocked";
                $this->ErrorReason = self::ERROR_REASON_BLOCK;
                $retry = true;
            } else {
                $retry = $this->doRetry();

                if ($this->http->FindSingleNode('//p[contains(text(), "Error 403 - You don\'t have enough permissions to proceed further")]')) {
                    $this->markProxyAsInvalid();

                    throw new \CheckRetryNeededException(3, 0, self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                }

                if ($this->http->FindSingleNode('//span[contains(text(), "This site can’t be reached")] | //body[contains(text(), "An error (502 Bad Gateway) has occurred in response to this request.")]')) {
                    $this->markProxyAsInvalid();

                    throw new \CheckRetryNeededException(3, 0, self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                }
            }
            // freezing script workaround
            if (strstr($this->http->currentUrl(), 'https://www.britishairways.com/travel/loginr/public')
                && $this->http->FindPreg("/<noscript>Please enable JavaScript to view the page content.<\/noscript>/")) {
                $retry = true;
            }

            if ($this->http->FindPreg("/^<head><\/head><body><\/body>$/")) {
                $retry = true;
            }

            return $this->checkErrors();
        }// if (!$loginInput)

        if ($ensCloseBanner = $this->waitForElement(\WebDriverBy::xpath('//button[@id = "ensCloseBanner"]'), 0)) {
            $ensCloseBanner->click();
            $this->saveResponse();
        }

        $this->logger->notice("js injection");

        /*
        try {
            $this->driver->executeScript("
                $('#ensCloseBanner').click();
                $('input[name=membershipNumber], input#loginid, input#username').val('{$this->AccountFields['Login']}');
                $('input[name=password], input#password').val('" . str_replace(["\\", "'"], ["\\\\", "\'"], $this->AccountFields['Pass']) . "');");
        } catch (UnexpectedJavascriptException | WebDriverException $e) {
            $this->logger->error("Exception: " . $e->getMessage(), ['HtmlEncode' => true]);
        */

        $loginInput = $this->waitForElement(\WebDriverBy::xpath('//input[@id = "username"]'), 0);
        $passInput = $this->waitForElement(\WebDriverBy::xpath('//input[@id = "password"]'), 0);
        $btn = $this->waitForElement(\WebDriverBy::xpath('//button[@name="action"]'), 0);

        if (!$loginInput || !$passInput || !$btn) {
            return $this->checkErrors();
        }

        $this->saveResponse();
//        $mover = new \MouseMover($this->driver);
//        $mover->logger = $this->logger;
//        $mover->sendKeys($loginInput, $this->AccountFields['Login'], 5);
        $loginInput->sendKeys($this->AccountFields['Login']);
        $passInput->sendKeys($this->AccountFields['Pass']);

//        $this->driver->executeScript("try { document.querySelector('#checkbox').click(); } catch {}; ");
//        sleep(5);
        $captcha = $this->parseCaptcha();

        if ($captcha !== false) {
            $this->driver->executeScript("try { document.querySelector('iframe[data-hcaptcha-response]').setAttribute('data-hcaptcha-response', '{$captcha}'); } catch {}; ");
            $this->driver->executeScript("try { document.querySelector('[name=\"g-recaptcha-response\"]').value = '{$captcha}'; } catch {};");
            $this->driver->executeScript("try { document.querySelector('[name=\"h-captcha-response\"]').value = '{$captcha}'; } catch {};");
            $this->driver->executeScript("try { document.querySelector('input[name=\"captcha\"]').value = '{$captcha}'; } catch {};");
        }

        $this->saveResponse();
        $btn->click();
        /*
        }
        */
        $this->driver->executeScript("var remember = document.getElementById('rememberMe'); if (remember) remember.checked = true;");
        $this->saveResponse();

        return true;
    }

    public function Login()
    {
        if ($this->attempt > 0 && $this->isHot) {
            return true;
        }

        if ($this->debugMode) {
            if ($input = $this->waitForElement(\WebdriverBy::xpath("//input[@name='code']"), 10)) {
                $code = $this->getGoogleAuthCode($this->secret);
                $input->sendKeys($code);

                $btn = $this->waitForElement(\WebDriverBy::xpath("//button[@name='action']"), 0);
                $btn->click();
            }
        }

        // Sign In
        $this->waitFor(function () {
            $timeout = 0;

            if ($btn = $this->waitForElement(\WebDriverBy::xpath('//button[@id = "ecuserlogbutton"]'), 0)) {
                $this->saveResponse();
                $this->logger->debug('btn->click');
                $btn->click();
                $timeout = 7;
            }

            if ($btn = $this->driver->findElement(\WebDriverBy::xpath('//button[@id = "ensAcceptAll"]'))) {
                $this->logger->notice("close cookies popup");
                $this->logger->debug('ensAcceptAll click');
                $btn->click();
                sleep(7);
                $this->saveResponse();
            }

            return $this->waitForElement(\WebDriverBy::xpath('
                //li[@class = "logout"]/a[@class = "logOut" and normalize-space() = "Log out"]
                | //input[@id="membershipNumber"]
                | //div[contains(@class, "warning") and not(@hidden)]
                | //p[contains(text(), "Unfortunately access to the web page you were trying to visit has been blocked as our systems have detected unusual traffic from your computer network.")]
                | //h3[contains(text(), "We need to confirm your identity")]
                | //p[contains(text(), "We\'ve sent an email with your code")]
                | //p[contains(text(), "We\'ve sent a text message to")]
                | //p[contains(text(), "Check your preferred one-time password application for a code")]
                | //p[contains(text(), "Two-factor authentication is an extra layer of security that ")]
                | //h3[contains(text(), "We have updated our Terms and Conditions.")]
                | //p[contains(text(), "Sorry we can\'t show you this page at the moment.")]
                | //span[contains(text(), "This site can’t be reached")]
                | //span[@jsselect="heading" and contains(text(), "This page isn’t working")]
                | //span[contains(text(), "No internet")]
                | //body[contains(text(), "An error (502 Bad Gateway) has occurred in response to this request.")]
                | //p[contains(text(), "Error 403 - You don\'t have enough permissions to proceed further")]
                | //span[contains(text(), "This site can’t be reached")]
                | //span[contains(text(), "This page isn’t working")]
                | //span[contains(text(), "Your connection was interrupted")]
                | //h1[contains(text(), "Welcome to your Executive Club")]
                | //h1[contains(text(), "Oops, this page isn&rsquo;t available right now...")]
                | //span[contains(@class, "ulp-input-error-message")]
                | //div[@id = "prompt-alert"]/p
                | //h1[contains(text(), "Sorry, we couldn\'t log you in")]
                | //pre[contains(text(), "Bad Request")]
                | //p[contains(text(), "We are experiencing high demand on ba.com at the moment.")]
                | //button[contains(., "Try another method")]
            '), $timeout);
        }, 50);
        $this->saveResponse();

        if ($this->http->FindSingleNode("//p[contains(text(), 'Unfortunately access to the web page you were trying to visit has been blocked as our systems have detected unusual traffic from your computer network.')] | //pre[contains(text(), 'Bad Request')]")) {
            $this->DebugInfo = "Request has been blocked";
            $this->ErrorReason = self::ERROR_REASON_BLOCK;
            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(3, 3);
        }

        if ($message = $this->http->FindSingleNode('//h1[contains(text(), "Sorry, we couldn\'t log you in")]')) {
            $this->captchaReporting($this->recognizer);

            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        if ($this->http->FindSingleNode('//p[contains(text(), "We\'ve sent a text message to")] | //p[contains(text(), "Check your preferred one-time password application for a code")]')) {
            $this->captchaReporting($this->recognizer);
            $btnAnotherMethod = $this->waitForElement(\WebDriverBy::xpath("//button[contains(., 'Try another method')]"), 0);

            if ($btnAnotherMethod) {
                $btnAnotherMethod->click();

                $emailOption =
                    $this->waitForElement(\WebDriverBy::xpath('//button[@aria-label = "Email"]'), 5)
                    ?? $this->waitForElement(\WebDriverBy::xpath('//button[@aria-label = "SMS"]'), 0)
                    ?? $this->waitForElement(\WebDriverBy::xpath('//button[@aria-label = "Google Authenticator or similar"]'), 0)
                ;
                $this->saveResponse();

                if ($emailOption) {
                    $emailOption->click();

                    $this->waitForElement(\WebDriverBy::xpath("//p[contains(text(), 've sent an email with') or contains(text(), 'Check your preferred one-time password application for a code')]"), 5);
                    $this->saveResponse();
                }
            }// if ($btnAnotherMethod)
        }// if ($this->http->FindSingleNode('//h1[contains(text(), "We\'ve sent a text message to")]'))

        if ($this->http->ParseForm("captcha_form") && $this->http->FindSingleNode('//div[@data-captcha-sitekey]/@data-captcha-sitekey')) {
            $this->captchaReporting($this->recognizer, false);
//            throw new CheckRetryNeededException(0, 7, self::CAPTCHA_ERROR_MSG);
            $captcha = $this->parseCaptcha();

            if ($captcha === false) {
                return false;
            }

            $this->driver->executeScript("document.querySelector('iframe[data-hcaptcha-response]').setAttribute('data-hcaptcha-response', '{$captcha}');");
            $this->driver->executeScript("document.querySelector('[name=\"g-recaptcha-response\"]').value = '{$captcha}';");
            $this->driver->executeScript("document.querySelector('[name=\"h-captcha-response\"]').value = '{$captcha}';");
            $this->driver->executeScript("document.querySelector('input[name=\"captcha\"]').value = '{$captcha}';");

            $passInput = $this->waitForElement(\WebDriverBy::xpath('//input[@id = "password"]'), 0);
            $passInput->sendKeys($this->AccountFields['Pass']);

            $submit = $this->waitForElement(\WebDriverBy::xpath('//button[@name="action"]'), 0);
            $this->saveResponse();

            if (!$submit) {
                return $this->checkErrors();
            } else {
                $this->logger->debug("Submit button was found");
            }

            $submit->click();
            // Sign In
            $this->waitFor(function () {
                $timeout = 0;

                if ($btn = $this->driver->findElement(\WebDriverBy::xpath('//button[@id = "ensAcceptAll"]'))) {
                    $this->logger->notice("close cookies popup");
                    $this->logger->debug('ensAcceptAll click');
                    $btn->click();
                    sleep(7);
                    $this->saveResponse();
                }

                return $this->waitForElement(\WebDriverBy::xpath('
                //li[@class = "logout"]/a[@class = "logOut" and normalize-space() = "Log out"]
                | //input[@id="membershipNumber"]
                | //div[contains(@class, "warning") and not(@hidden)]
                | //p[contains(text(), "Unfortunately access to the web page you were trying to visit has been blocked as our systems have detected unusual traffic from your computer network.")]
                | //h3[contains(text(), "We need to confirm your identity")]
                | //p[contains(text(), "We\'ve sent an email with your code")]
                | //p[contains(text(), "We\'ve sent a text message to")]
                | //p[contains(text(), "Check your preferred one-time password application for a code")]
                | //p[contains(text(), "Two-factor authentication is an extra layer of security that ")]
                | //h3[contains(text(), "We have updated our Terms and Conditions.")]
                | //p[contains(text(), "Sorry we can\'t show you this page at the moment.")]
                | //span[contains(text(), "This site can’t be reached")]
                | //span[@jsselect="heading" and contains(text(), "This page isn’t working")]
                | //span[contains(text(), "No internet")]
                | //body[contains(text(), "An error (502 Bad Gateway) has occurred in response to this request.")]
                | //p[contains(text(), "Error 403 - You don\'t have enough permissions to proceed further")]
                | //span[contains(text(), "This site can’t be reached")]
                | //span[contains(text(), "This page isn’t working")]
                | //span[contains(text(), "Your connection was interrupted")]
                | //h1[contains(text(), "Welcome to your Executive Club")]
                | //h1[contains(text(), "Oops, this page isn&rsquo;t available right now...")]
                | //span[contains(@class, "ulp-input-error-message")]
                | //div[@id = "prompt-alert"]/p
                | //h1[contains(text(), "Sorry, we couldn\'t log you in")]
                | //pre[contains(text(), "Bad Request")]
                | //p[contains(text(), "We are experiencing high demand on ba.com at the moment.")]
                | //button[contains(., "Try another method")]
            '), $timeout);
            }, 50);
            $this->saveResponse();

            $this->waitForElement(\WebDriverBy::xpath("//th[contains(text(), 'Membership number')]/following-sibling::td[1]"), 0);

            // capthca error
            if ($capthcaError = $this->waitForElement(\WebDriverBy::xpath("//p[contains(text(), 'Error parsing response') or contains(text(), 'You did not validate successfully. Please try again.')]"), 0)) {
                $this->logger->error(">>> " . $capthcaError->getText());

                throw new \CheckRetryNeededException(3, 7, self::CAPTCHA_ERROR_MSG);
            }

            // save page to logs
            $this->logger->debug("save page to logs");
            $this->saveResponse();
        }// if ($this->http->ParseForm("captcha_form") && $this->http->FindSingleNode('//span[contains(., "Verify you are human")]'))

        // Two Factor Authentication    // refs #14276
        if ($this->http->FindSingleNode("//h3[contains(text(), 'We need to confirm your identity')]")
            && ($btnContinue = $this->waitForElement(\WebDriverBy::xpath("//form[contains(@action, 'twofactorauthentication') or contains(@class, 'form')]/button[contains(text(), 'Continue')]"), 0))) {
            $this->captchaReporting($this->recognizer);
            $this->logger->notice("Two Factor Authentication Login");
            $btnContinue->click();
            $this->driver->executeScript("try { $('button.continue-button, button#action-btn').click(); } catch (e) {}");
            $this->waitForElement(\WebDriverBy::xpath("//form[@id = 'select-option']//input[@id ='email']"), 5);
        }

        if ($this->http->FindSingleNode("//h3[contains(text(), 'We have updated our Terms and Conditions.')]")) {
            $this->captchaReporting($this->recognizer);
            $this->logger->notice("We have updated our Terms and Conditions");
            $this->logger->notice("Current Url: " . $this->http->currentUrl());

            if (property_exists($this, 'isRewardAvailability') && $this->isRewardAvailability
                && ($btnAgree = $this->waitForElement(\WebDriverBy::xpath("//*[self::a or self::button][contains(.,'Agree and continue')]"), 0))
            ) {
                $btnAgree->click();
            } else {
                $this->throwAcceptTermsMessageException();
            }
        }

        if ($this->http->FindSingleNode('//p[contains(text(), "Two-factor authentication is an extra layer of security that ")]')) {
            $this->captchaReporting($this->recognizer);
            $this->throwProfileUpdateMessageException();
        }

        // retries - This page is not available.
        if ($loginFail = $this->waitForElement(\WebDriverBy::xpath('
                //p[contains(text(), "Sorry we can\'t show you this page at the moment.")]
                | //span[contains(text(), "This site can’t be reached")]
                | //span[contains(text(), "This page isn’t working")]
                | //span[@jsselect="heading" and contains(text(), "This page isn’t working")]
                | //body[contains(text(), "An error (502 Bad Gateway) has occurred in response to this request.")]
                | //button[contains(text(), "Please wait...")]
                | //p[contains(text(), "Error 403 - You don\'t have enough permissions to proceed further")]
                | //span[contains(text(), "Your connection was interrupted")]
                | //h1[contains(text(), "Oops, this page isn&rsquo;t available right now...")]
                | //p[contains(text(), "We are experiencing high demand on ba.com at the moment.")]
            '), 0)
        ) {
            $this->logger->error(">>> " . $loginFail->getText());

            if (
                /*
                $this->attempt > 0
                &&
                */
                trim($loginFail->getText()) == 'An error (502 Bad Gateway) has occurred in response to this request.'
            ) {
                throw new \CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            if ($this->http->FindSingleNode('//p[contains(text(), "Error 403 - You don\'t have enough permissions to proceed further")]')) {
                $this->markProxyAsInvalid();

                throw new \CheckRetryNeededException(3, 0, self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(3, 0);
        } else {
            $this->logger->notice(">>> error 'This page is not available.' not found");
        }

        $this->logger->debug("[Current URL]: {$this->http->currentUrl()}");

        // Two Factor Authentication    // refs #14276
        if ($this->parseQuestion()) {
            $this->captchaReporting($this->recognizer);

            return false;
        }

        // Confirm contact details
        if ($this->http->FindSingleNode("//p[contains(text(), 'confirm the details displayed')]")
            && ($link = $this->http->FindSingleNode("//a[contains(@href, 'main_nav&link=main_nav') and strong[contains(text(), 'My Executive Club')]]/@href"))) {
            $this->logger->notice("Skip update account details");
            $this->http->GetURL($link);
        }
        // You have not yet validated your email address
        if ($this->http->FindPreg('/You have not yet validated your email address/ims')
            // We are currently missing the following information from your details
            || $this->http->FindPreg("/We are currently missing the following information from your details\. To keep your details up to date please complete\/amend the fields below/ims")
        ) {
            $this->captchaReporting($this->recognizer);
            $this->throwProfileUpdateMessageException();
        }
        /*
         * Sorry, you have made too many invalid login attempts.
         * We don't have an email address associated with your account,
         * therefore we cannot send you a new PIN/Password.
         */
        if ($message =
            $this->http->FindPreg("/(Sorry, you have made too many invalid login attempts\.\s*We don\'t have an email address associated with your account\,\s*therefore we cannot send you a new PIN\/Password\.)/ims")
            ?? $this->http->FindSingleNode('//h1[contains(text(), "Your account is now locked")]')
        ) {
            $this->captchaReporting($this->recognizer);

            throw new \CheckException($message, ACCOUNT_LOCKOUT);
        }
        // Please change your PIN to a password
        if ($message = $this->http->FindSingleNode("//h1[contains(text(), 'Please change your PIN to a password')]")) {
            $this->captchaReporting($this->recognizer);

            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        // We regret to advise that this section of the site is temporarily unavailable
        if ($message = $this->http->FindPreg("/t-logo-topic-content\">\s*<p>\s*([^<]+)/ims")) {
            $this->captchaReporting($this->recognizer);

            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        // Please change your PIN to a password
        if ($this->http->FindSingleNode("//h1[contains(text(), 'Please change your PIN to a password')]")
            // Please could you confirm the details displayed, amend or supply them as necessary.
            || $this->http->FindSingleNode("//div[contains(text(), 'Please could you confirm the details displayed, amend or supply them as necessary.')]")
            // We have had problems delivering information to you.
            || $this->http->FindSingleNode("//p[contains(text(), 'We have had problems delivering information to you.')]")
        ) {
            $this->captchaReporting($this->recognizer);
            // throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            $this->http->GetURL('https://www.britishairways.com/travel/echome/execclub/_gf/en_' . $this->getCountryCode() . '?link=main_nav');

            $this->waitForElement(\WebDriverBy::xpath('
                //li[@class = "logout"]/a[@class = "logOut" and normalize-space() = "Log out"]
            '), 10);
            $this->saveResponse();
        }

        $notError = $this->http->FindPreg("/(Welcome to)/ims");

        if (
            (isset($notError) && !strstr($this->http->currentUrl(), 'https://www.britishairways.com/travel/loginr/public/en_'))
            || $this->http->FindSingleNode("(//a[contains(text(), 'Log out')])[1]")
        ) {
            $this->captchaReporting($this->recognizer);
            $this->DebugInfo = null;
            $this->markProxySuccessful();

            return true;
        }

        // Invalid password
        if ($message = $this->http->FindSingleNode("//li[contains(text(), 'We are not able to recognise the')]")) {
            if ($this->isRewardAvailability) {
                throw new \CheckException($message, ACCOUNT_LOCKOUT);
            }

            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode("(//*[contains(text(), 'The username and password do not match what is held on our system')])[1]")) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode("(//*[contains(text(), 'You had more than one Executive Club account')])[1]")) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode("(//*[contains(text(), 'It has not been possible to log in as our records')])[1]")) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode("(//*[contains(text(), 'We are unable to find your username')])[1]")) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode("(//*[contains(text(), 'Password cannot be e-mailed as no email address is present')])[1]")) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode("(//*[contains(text(), 'The Login ID you have entered does not match what is held on our system')])[1]")) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode("(//*[contains(text(), 'The userid/password you have entered is not correct')])[1]")) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode('(//*[contains(text(), "Sorry, we don\'t recognise the membership number or PIN/password you have entered")])[1]')) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode('(//*[contains(text(), "Sorry, we don\'t recognise the email address you have entered")])[1]')) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode('(//*[contains(text(), "The email address you have entered is already being used on another Executive Club account")])[1]')) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode('(//*[contains(text(), "You are requested to change your password. Please enter a new password.")])[1]')) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        if ($message = $this->http->FindSingleNode('(//*[contains(text(), "Invalid frequent flyer status.")])[1]')) {
            throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
        }

        // Unable to process request please retry later.
        if ($message = $this->http->FindSingleNode('(//*[contains(text(), "Unable to process request please retry later")])[1]')) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Error While getting customer details
        if ($message = $this->http->FindSingleNode('(//*[contains(text(), "Error While getting customer details")])[1]')) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Sorry, there seems to be a technical problem
        if ($message = $this->http->FindSingleNode('//p[contains(text(), "Sorry, there seems to be a technical problem")]')) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Sorry, there's a problem with our systems. Please try again, and if it still doesn't work, you might want to try again later.
        if ($message = $this->http->FindSingleNode('//li[contains(text(), "Sorry, there\'s a problem with our systems. Please try again, and if it still doesn\'t work, you might want to try again later.")]')) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        //# There is a problem in logging into the system. Please try later.
        if ($message = $this->http->FindPreg("/(There is a problem in logging into the system\.\s*Please try later\.)/ims")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Please update your details
        if ($this->http->FindSingleNode("//span[contains(text(), 'We are currently missing the following information from your details')]")) {
            $this->throwProfileUpdateMessageException();
        }
        // You have made too many invalid login attempts
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'You have made too many invalid login attempts') or contains(text(), 'Your account is now locked for up to 24 hours')]")) {
            throw new \CheckException($message, ACCOUNT_LOCKOUT);
        }
        // Your account has been locked due to too many invalid login attempts.
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Your account has been locked due to too many invalid login attempts.')]")) {
            throw new \CheckException($message, ACCOUNT_LOCKOUT);
        }
        /*
         * Your account is temporarily unavailable
         *
         * We have locked your account temporarily to keep it safe and secure.
         * For more information please refer to the email or letter you received from us.
         */
        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'We have locked your account temporarily to keep it safe and secure.')]")) {
            throw new \CheckException($message, ACCOUNT_LOCKOUT);
        }
        // Your user id has been locked
        if ($message = $this->http->FindSingleNode("(//*[contains(text(), 'Your user id has been locked')])[1]")) {
            throw new \CheckException($message, ACCOUNT_LOCKOUT);
        }
        // We're sorry, but ba.com is very busy at the moment, and couldn't deal with your request.
        if ($message = $this->http->FindSingleNode('(//*[contains(text(), "We\'re sorry, but ba.com is very busy at the moment, and couldn\'t deal with your request.")])[1]')) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        if ($message = $this->http->FindSingleNode('//h1[contains(text(), "Oops, this page isn&rsquo;t available right now...")]')) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        if ($this->http->FindSingleNode('//p[contains(text(), "Error 403 - You don\'t have enough permissions to proceed further")]')) {
            $this->DebugInfo = "request has been blocked";
            $this->ErrorReason = self::ERROR_REASON_BLOCK;

            return false;
        }

        if ($message = $this->http->FindSingleNode('(//div[contains(@class, "warning") and not(@hidden)] | //span[contains(@class, "ulp-input-error-message") and normalize-space(.) != ""] | //div[@id = "prompt-alert"]/p)[1]')) {
            $this->logger->error("[Error]: {$message}");

            if ($message == "Verify you are human.") {
                $this->captchaReporting($this->recognizer, false);

                throw new \CheckRetryNeededException(0, 7, self::CAPTCHA_ERROR_MSG);
            }

            $this->captchaReporting($this->recognizer);

            if (
                strstr($message, 'Sorry, something went wrong. Please try again or')
                || strstr($message, 'We are having technical difficulties verifying your credentials')
            ) {
                throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            if (strstr($message, 'We couldn\'t sign you in at the moment. Please review your login details.')) {
                throw new \CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (strstr($message, 'We have detected a potential security issue with this account.')) {
                throw new \CheckException($message, ACCOUNT_LOCKOUT);
            }

            $this->DebugInfo = $message;

            // TODO: provider bug workaround
            if (!strstr($message, 'Please complete the following fields Membership number')) {
                return false;
            }// if (strstr($message, 'Please complete the following fields Membership number'))
        }

        // TODO: provider bug workaround
        if (
            $this->http->FindSingleNode("//input[@id='membershipNumber']/@id")
            && $this->tryOldForm === false
        ) {
            $this->tryOldForm = true;

            try {
                $this->logger->notice("js injection");
                $this->driver->executeScript("
                        $('input[name=membershipNumber], input#loginid, input#username').val('{$this->AccountFields['Login']}');
                        $('input[name=password], input#password').val('" . str_replace(["\\", "'"], ["\\\\", "\'"], $this->AccountFields['Pass']) . "');
                    ");
            } catch (\UnexpectedJavascriptException $e) {
                $this->logger->error("Exception: " . $e->getMessage(), ['HtmlEncode' => true]);
            }

            if ($btn = $this->waitForElement(\WebDriverBy::xpath('//button[@id = "ecuserlogbutton"]'), 0)) {
                $this->saveResponse();
                $this->logger->debug('btn->click');
                $btn->click();
                $timeout = 7;
                sleep($timeout);
                $this->saveResponse();

                return $this->Login();
            }// if ($btn = $this->waitForElement(WebDriverBy::xpath('//button[@id = "ecuserlogbutton"]'), 0))
        }// if (strstr($message, 'Please complete the following fields Membership number'))

        if ($this->http->FindSingleNode('//button[@id = "ecuserlogbutton"]')) {
            throw new \CheckRetryNeededException(3, 7);
        }

        return $this->checkErrors();
    }

    public function ParseRewardAvailability(array $fields)
    {
        $this->logger->notice('logged in, Save session');
        $this->keepSession(true);

        $this->logger->info("Parse Reward Availability", ['Header' => 2]);
        $this->logger->debug("params: " . var_export($fields, true));

        $this->logger->notice(
            'parsing started at: ' . date("H:i:s", $this->requestDateTime)
        );

        $this->inDateDep = $fields['DepDate'];

        if ($fields['DepDate'] > strtotime('+355 day')) {
            $this->SetWarning('too late');

            return [];
        }

        if ($fields['Currencies'][0] !== 'USD') {
            $fields['Currencies'][0] = $this->getRewardAvailabilitySettings()['defaultCurrency'];
            $this->logger->notice("parse with defaultCurrency: " . $fields['Currencies'][0]);
        }
        $this->inCabin = $fields['Cabin'];
        $fields['Cabin'] = $this->getCabinFields(false)[$fields['Cabin']];
        $countryCode = $this->getCountryCode();

        $this->depDateIn = $fields['DepDate'];

        if ($countryCode === 'us') {
            $fields['DepDate'] = date("m/d/y", $fields['DepDate']);
        } else {
            $fields['DepDate'] = date("d/m/y", $fields['DepDate']);
        }

        try {
            $result = $this->ParseReward($fields);
        } catch (\WebDriverException | \WebDriverCurlException $e) {
            $this->logger->error('WebDriverException: ' . $e->getMessage());

            throw new \CheckRetryNeededException(5, 0);
        }

        return ['routes' => $result];
    }

    public function checkErrors($http = null)
    {
        if (!isset($http)) {
            $http = $this->http;
        }
        $this->logger->notice(__METHOD__);
        // Sorry, our website is unavailable while we make a quick update to our systems.
        if ($message = $http->FindSingleNode("
                //p[contains(text(), 'Sorry, our website is unavailable while we make a quick update to our systems.')]
                | //p[contains(text(), 'Both ba.com and our apps are temporarily unavailable while we make some planned improvements to our systems.')]
            ")
        ) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // System Upgrade
        if ($message = $http->FindSingleNode("//p[contains(text(), 'Due to the Executive Club System Upgrade you will experience limited access to your account')]")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // We regret to advise that this section of the site is temporarily unavailable.
        if ($message = $http->FindPreg("/(We regret to advise that this section of the site is temporarily unavailable\.)/ims")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Unfortunately our systems are not responding
        if ($message = $http->FindSingleNode("//p[contains(text(),'Unfortunately our systems are not responding')]")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // We are currently carrying out site maintenance between ...
        if ($message = $http->FindSingleNode("//p[contains(text(), 'We are currently carrying out site maintenance')]")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // There is currently no access to your account while we upgrade our system
        if ($message = $http->FindSingleNode("//li[contains(text(),'There is currently no access to your account while we upgrade our system')]")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // Sorry, there seems to be a technical problem. Please try again in a few minutes.
        if ($message = $http->FindSingleNode("//p[contains(text(), 'Sorry, there seems to be a technical problem. Please try again in a few minutes.')]")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // We are experiencing technical issues today with our website.
        if ($message = $http->FindSingleNode("//p[contains(text(), 'We are experiencing technical issues today with our website.')]")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        /*
         * Sorry, there seems to be a technical problem. Please try again in a few minutes, and please contact us if it still doesn't work.
         * We apologise for the inconvenience.
         */
        if ($message = $http->FindSingleNode("//p[contains(text(), 'Sorry, there seems to be a technical problem. Please try again in a few minutes')]")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        /*
         * Major IT system failure . latest information at 23.30 Saturday May 27
         *
         * Following the major IT system failure experienced throughout Saturday,
         * we are continuing to work hard to fully restore all of our global IT systems.
         *
         * Flights on Saturday May 27
         */
        if ($message = $http->FindSingleNode("//p[contains(text(), 'Following the major IT system failure experienced throughout')]")) {
            throw new \CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        if (
            // Internal Server Error - Read
            $http->FindSingleNode("//h1[contains(text(), 'Internal Server Error - Read')]")
            || $http->FindSingleNode("//h1[contains(text(), '504 Gateway Time-out')]")
            || $http->FindSingleNode("//h1[contains(text(), '502 Bad Gateway')]")
            || $http->FindPreg("/An error occurred while processing your request\./")
        ) {
            throw new \CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        $this->logger->debug("[URL]: " . $http->currentUrl());
        $this->logger->debug("[CODE]: " . $http->Response['code']);
        // retries
        if (in_array($http->Response['code'], [0, 301, 302, 403])
            || ($http->Response['code'] == 200 && empty($http->Response['body']))) {
            if ($http->FindSingleNode('//p[contains(text(), "Error 403 - You don\'t have enough permissions to proceed further")]')) {
                throw new \CheckRetryNeededException(3, 0, self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            throw new \CheckRetryNeededException(3, 5);
        // error in selenium
        } elseif (
            $http->Response['code'] == 200
            && $http->FindSingleNode('//p[contains(text(), "Error 403 - You don\'t have enough permissions to proceed further") or contains(text(), "We are experiencing high demand on ba.com at the moment.")]')
        ) {
            $this->markProxyAsInvalid();

            throw new \CheckRetryNeededException(3, 0);
        }

        $this->logger->debug('[checkErrors. date: ' . date('Y/m/d H:i:s') . ']');

        return false;
    }

    protected function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);

        $this->saveResponse();
//        $key = $this->http->FindSingleNode('//div[@data-captcha-sitekey]/@data-captcha-sitekey');
//        $captchaType = $this->http->FindSingleNode('//div[@data-captcha-sitekey]/@data-captcha-provider');

//        if (!$key) {
//            return false;
//        }

//        $postData = [
//            "type"           => "HCaptchaTaskProxyless",
//            "websiteURL"     => $this->http->currentUrl(),
//            "websiteKey"     => $key,
//        ];
//        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_ANTIGATE_API_V2);
//        $this->recognizer->RecognizeTimeout = 120;
//
//        return $this->recognizeAntiCaptcha($this->recognizer, $postData);
//
//        if ($captchaType == 'hcaptcha') {
//            $this->recognizer = $this->getCaptchaRecognizer();
//            $this->recognizer->RecognizeTimeout = 120;
//            $parameters = [
//                "method"  => "hcaptcha",
//                "pageurl" => $this->http->currentUrl(),
//                "proxy"   => $this->http->GetProxy(),
//                "domain"  => "js.hcaptcha.com",
//            ];
//        } else {
//            $this->sendNotification('refs #24913 - need to check capthca type // IZ');
//            $this->recognizer = $this->getCaptchaRecognizer();
//            $this->recognizer->RecognizeTimeout = 120;
//            $parameters = [
//                "method"  => "turnstile",
//                "pageurl" => $this->http->currentUrl(),
//                "proxy"   => $this->http->GetProxy(),
//                "sitekey" => $key,
//            ];
//        }

//        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);

        $browser = new \HttpBrowser("none", new \CurlDriver());

        $browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
        $browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
        $browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));

        $this->http->brotherBrowser($browser);

        $key = 'aecc8a7b146f72b86a0a07319ef42ccd';
        $sitekey = $this->http->FindSingleNode('//div[@data-captcha-sitekey]/@data-captcha-sitekey');
//        $sitekey = $sitekey->getAttribute('data-sitekey');

        if (!$sitekey) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 180;

        $payload = http_build_query([
            "key"     => $key,
            "method"  => "hcaptcha",
            "sitekey" => $sitekey,
            "pageurl" => $this->http->currentUrl(),
            "domain"  => "js.hcaptcha.com",
            "json"    => 1,
        ]);

        $url = "https://api.solvecaptcha.com/in.php?$payload";

        $browser->GetURL($url);

        $response = $browser->JsonLog(null, 1, true);

        $payload = http_build_query([
            "key"    => $key,
            "action" => "get",
            "id"     => $response['request'],
            "json"   => 1,
        ]);

        do {
            sleep(10);

            $url = "https://api.solvecaptcha.com/res.php?$payload";

            $browser->GetURL($url);
            $response = $browser->JsonLog(null, 1, true);
        } while ($response['status'] !== 1 && $response['request'] !== 'ERROR_CAPTCHA_UNSOLVABLE');

        $browser->cleanup();

        if ($response['request'] == 'ERROR_CAPTCHA_UNSOLVABLE') {
            $this->logger->debug('ERROR_CAPTCHA_UNSOLVABLE');

            throw new \CheckRetryNeededException(3, 0);
        }

        return $response['request'];
    }

    private function getCabinFields($onlyKeys = true): array
    {
        $cabins = [
            'economy'        => 'Economy',
            'premiumEconomy' => 'Premium economy',
            'firstClass'     => 'First',
            'business'       => 'Business Class',
        ];

        if ($onlyKeys) {
            return array_keys($cabins);
        }

        return $cabins;
    }

    private function initCurl()
    {
        $this->logger->notice(__METHOD__);

        if (isset($this->curl)) {
            $this->logger->debug('curl already exists');

            return;
        }

        $this->curl = new \HttpBrowser("none", new \CurlDriver());
        $this->curl->setProxyParams($this->http->getProxyParams());

        $this->http->brotherBrowser($this->curl);
        $cookies = $this->driver->manage()->getCookies();

        foreach ($cookies as $cookie) {
            $this->curl->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                $cookie['expiry'] ?? null);
        }
        $this->curl->setUserAgent($this->http->getDefaultHeader('User-Agent'));
        $this->curl->GetURL("https://ipinfo.io/ip");
    }

    private function ParseReward($fields = [], ?bool $isRetry = false)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info("ParseReward [" . $fields['DepDate'] . "-" . $fields['DepCode'] . "-" . $fields['ArrCode'] . "]",
            ['Header' => 2]);
        $countryCode = $this->getCountryCode();

        $mainUrl = "https://www.britishairways.com/travel/redeem/execclub/execclub/_gf/en_{$countryCode}?eId=106019&tab_selected=redeem&redemption_type=STD_RED";

        $this->http->GetURL($mainUrl);

        if (!$this->validRoute($fields)) {
            return [];
        }

        $this->initCurl();

        $this->curl->GetURL("https://totp.app/");

        if (strcasecmp($fields['Cabin'], 'Business/Club') === 0
            || strcasecmp($fields['Cabin'], 'Business Class') === 0
        ) {
            $cabin = 'Business Class';
            $fields['Cabin'] = 'Business/Club';
        } else {
            $cabin = ucwords(strtolower($fields['Cabin']));
            $fields['Cabin'] = ucfirst(strtolower($fields['Cabin']));
        }
//        $this->curl->GetURL("https://www.britishairways.com/travel/redeem/execclub?eId=106019&tab_selected=redeem&redemption_type=STD_RED");
        $this->curl->RetryCount = 0;
//        $this->curl->GetURL("https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$countryCode}?eId=106019&tab_selected=redeem&redemption_type=STD_RED", [], 30);
        $this->curl->GetURL($mainUrl, [], 30);

        if ($this->isBadProxy($this->curl)) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if (!$this->curl->ParseForm("plan_redeem_trip")) {
            $this->logger->debug('try again');
//            $this->curl->GetURL("https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$countryCode}?eId=106019&tab_selected=redeem&redemption_type=STD_RED", [], 30);
            $this->curl->GetURL("https://www.britishairways.com/travel/redeem/execclub/execclub/_gf/en_{$countryCode}?eId=106019&tab_selected=redeem&redemption_type=STD_RED",
                [], 30);

            if ($this->isBadProxy($this->curl)) {
                throw new \CheckRetryNeededException(5, 0);
            }
        }
        $this->curl->RetryCount = 2;

        if (!$this->curl->ParseForm("plan_redeem_trip")) {
            $this->logger->error('check parse');

            if ($this->curl->Response['code'] == 403) {
                throw new \CheckRetryNeededException(5, 0);
            }

            throw new \CheckException('not load plan_redeem_trip', ACCOUNT_ENGINE_ERROR);
        }

        if (!$this->fillRedeemTrip($fields, $cabin)) {
            if (!$isRetry) {
                return $this->ParseReward($fields, true);
            }

            throw new \CheckRetryNeededException(5, 0);
        }

        $this->postRedeemTrip($isRetry, $fields, $cabin);

        if ($this->curl->FindPreg("/There was a problem with your request, please try again later./")
            && ($link = $this->curl->FindSingleNode("//a[normalize-space()='Start again']/@href"))
        ) {
            $this->curl->NormalizeURL($link);
            $this->curl->GetURL($link);

            if (!$isRetry) {
                return $this->ParseReward($fields, true);
            }

            throw new \CheckRetryNeededException(5, 0);
        }

        if ($msg = $this->curl->FindSingleNode("//text()[contains(normalize-space(),'a problem with our systems. Please try again, and if it still doesn')]")) {
            $this->logger->error($msg);

            if ($this->attempt === 0) {
                throw new \CheckRetryNeededException(5, 0);
            }
            $this->logger->error($msg);

            throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
        }

        if ($msg = $this->curl->FindSingleNode("//text()[contains(.,'Sorry, there seems to be a technical problem. Please try again in a few minutes')]/ancestor::*[1]")) {
            $this->logger->error($msg);
            // TODO retry|restart doesn't help - надо проверять запросы

            throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
        }

        if ($msg = $this->curl->FindSingleNode("//h3[contains(.,'There is no availability on British Airways for the displayed date')]")) {
            $this->SetWarning($msg);

            return [];
        }

        if ($this->curl->FindSingleNode("//h2[contains(.,'Choose your travel dates')]")) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if ($msg = $this->curl->FindSingleNode("//h3[contains(.,'If there’s no availability on your chosen dates you can') or contains(.,\"If there's no availability on your chosen dates you can\")]")) {
            $this->SetWarning('There’s no availability on your chosen dates');

            return [];
        }

        if ($msg = $this->curl->FindSingleNode("//div[@id='blsErrors']//li[contains(.,'There was a problem with your request, please try again later')]")) {
            $this->SetWarning($msg);

            return [];
        }

        if ($msg = $this->curl->FindSingleNode('//div[@id="blsErrors"]//li[contains(.,"It looks like you\'re using multiple tabs") or contains(.,"Resource Not Found Error Received from ASSOCIATE API from AGL Group Loyalty Platform")]')) {
            $this->logger->error($msg);

            if (!$isRetry) {
                return $this->ParseReward($fields, true);
            }

            if ($this->attempt === 0) {
                throw new \CheckRetryNeededException(5, 0);
            }

            throw new \CheckException($msg, ACCOUNT_ENGINE_ERROR);
        }

        if ($msg = $this->curl->FindSingleNode("//div[@id='blsErrors']//li[contains(.,'British Airways and its partners do not fly this route. Please consider alternative destinations')]")) {
            $this->SetWarning($msg);

            return [];
        }

        if ($msg = $this->curl->FindSingleNode("(//div[@id='blsErrors']//li[normalize-space()!=''])[1]")) {
            $this->logger->error($msg);

            if (strpos($msg, 'Sorry, it is only possible to book flights up to 355 days in advance') !== false
                || strpos($msg, 'point has not been recognised. Please ensure you select a ') !== false
            ) {
                $this->SetWarning($msg);

                return [];
            }

            if ($this->curl->FindPreg("/Not able to connect to AGL Group Loyalty Platform and IO Error Recieved/",
                false, $msg)) {
                $this->SetWarning('No availability on your chosen dates');

                return [];
            }

            if ($this->curl->FindPreg("/We're sorry, but ba.com is very busy at the moment, and couldn't deal with your request. Please do try again/",
                false, $msg)) {
                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }

            if ($this->curl->FindPreg("/Internal Server Error Received from IAGL API ASSOCIATE/", false, $msg)) {
                if (!$isRetry) {
                    return $this->ParseReward($fields, true);
                }

                throw new \CheckException($msg, ACCOUNT_ENGINE_ERROR);
            }

            if ($this->curl->FindPreg("/Sorry, something went wrong. Please try again or contact us/", false, $msg)) {
                if (!$isRetry) {
                    return $this->ParseReward($fields, true);
                }

                throw new \CheckException($msg, ACCOUNT_ENGINE_ERROR);
            }

            if ($this->curl->FindPreg("/(Web Service Query Error|Web Service Connection Error|problem with your request. Please try again, and if you)/",
                false, $msg)) {
                if ($this->curl->FindPreg("/(problem with your request. Please try again, and if you)/", false, $msg)) {
                    $this->sendNotification("check retry after problem with your request // ZM");
                }

                if (!$isRetry) {
                    return $this->ParseReward($fields, true);
                }

                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }

            if ($this->curl->FindPreg("/(?:We are encountering a temporary fault, please try again. If the problem persists, please contact your|Sorry, something went wrong. Please try again or contact us)/",
                false, $msg)) {
                if (time() - $this->requestDateTime < $this->AccountFields['Timeout'] - 5) {
                    throw new \CheckRetryNeededException(5, 0);
                }

                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }

            if ($this->curl->FindPreg("/There is currently no access to your account while we upgrade our system./",
                false, $msg)) {
                if ($this->attempt !== 4 && (time() - $this->requestDateTime < $this->AccountFields['Timeout'] - 5)) {
                    throw new \CheckRetryNeededException(5, 10);
                }

                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }
            $this->sendNotification("check msg // ZM");
        }

        try {
            $routes = $this->parseRewardFlights($fields, $cabin);
        } catch (\CheckException $e) {
            if ($e->getMessage() === 'wrong format: departInputDate') {
                if (!$isRetry) {
                    return $this->ParseReward($fields, true);
                }

                throw new \CheckException('Something went wrong', ACCOUNT_ENGINE_ERROR);
            }

            throw $e;
        }

        return $routes;
    }

    private function validRoute(array $fields): bool
    {
        if (!$this->hasAirport($fields['DepCode'])) {
            $this->SetWarning('no flights from ' . $fields['DepCode']);

            return false;
        }

        if (!$this->hasAirport($fields['ArrCode'])) {
            $this->SetWarning('no flights to ' . $fields['ArrCode']);

            return false;
        }

        if (count($this->ports) !== 2) {
            $this->logger->error("has no airport details");
            $this->logger->notice(var_export($this->ports, true), ['pre' => true]);

            throw new \CheckRetryNeededException(5, 0);
        }

        $this->logger->notice(var_export($this->ports, true), ['pre' => true]);

        return true;
    }

    private function hasAirport($airport): bool
    {
        $data = \Cache::getInstance()->get('ra_british_locations' . $airport);

        if ($data === false || !is_string($data)) {
            $headers = [
                "Accept"           => "*/*",
                "Accept-Language"  => "en-US,en;q=0.5",
                "X-Requested-With" => "XMLHttpRequest",
            ];
            // not award
            // "https://www.britishairways.com/api/sc4/csdm-plm/rs/v1/productlocations;searchText=LAX?locale=en_US"
            $url = "https://www.britishairways.com/dwr/exec/locationHelper.getMatchedLocations.dwr?callCount=1&c0-scriptName=locationHelper&c0-methodName=getMatchedLocations&xml=true&c0-param0=string:" . $airport;
            $referer = "https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$this->getCountryCode()}?eId=106019&tab_selected=redeem&redemption_type=STD_RED";
            $data = $this->getFetch('airport' . $airport, $url, 'GET', $headers, [], $referer);

            if (strpos($data, 'var s0={}') === false) {
//                $this->sendNotification("check airport " . $airport . " // ZM");

                throw new \CheckRetryNeededException(5, 0);
            }

            \Cache::getInstance()->set('ra_british_locations' . $airport, $data, 60 * 60 * 24 * 7);
        }

        $stringNumber = $this->http->FindPreg("/s0\['" . $airport . "']=(s\d+)/", false, $data);

        if (null === $stringNumber) {
            return false;
        }
        $this->ports[$airport] = $this->http->FindPreg('/' . $stringNumber . '="([^"]+)"/', false, $data);

        if (empty($this->ports[$airport])) {
            $this->sendNotification("check airport " . $airport . " (2) // ZM");

            throw new \CheckRetryNeededException(5, 0);
//            throw new \CheckException("check airport", ACCOUNT_ENGINE_ERROR);
        }

        return true;
    }

    private function getXHR($url, $method, array $headers)
    {
        $headersString = "";

        foreach ($headers as $key => $value) {
            $headersString .= 'xhttp.setRequestHeader("' . $key . '", "' . $value . '");
        ';
        }

        $script = '
                var xhttp = new XMLHttpRequest();
                xhttp.withCredentials = true;
                xhttp.open("' . $method . '", "' . $url . '", false);
                ' . $headersString . '
                var responseText = null;
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && (this.status == 200 || this.status == 202)) {
                        responseText = this.responseText;
                    }
                };
                xhttp.send();
                return responseText;
            ';
        $this->logger->debug("[run script]:");
        $this->logger->debug($script, ['pre' => true]);

        return $this->driver->executeScript($script);
    }

    private function getFetch($id, $url, $method, array $headers, $payload, $referer)
    {
        $this->logger->notice(__METHOD__);

//        if (!isset($headers['Referer'])) {
//            $headers['Referer'] = $referer;
//        }
//
//        return $this->getXHR("POST", $url, $headers);

        $headers = json_encode($headers);
//        $payload = base64_encode($payload);
//                   "body": atob("' . $payload . '"),
        $script = '
                fetch("' . $url . '", {
                  "headers": ' . $headers . ',
                  "referrer": "' . $referer . '",
                  "method": "' . $method . '",
                  "mode": "cors",
                  "credentials": "include"
                }).then( response => response.text())
                  .then( result => {
                    let script = document.createElement("script");
                    let id = "' . $id . '";
                    script.id = id;
                    script.setAttribute(id, result);
                    document.querySelector("body").append(script);
                })
                .catch(error => {                    
                    let newDiv = document.createElement("div");
                    let id = "' . $id . '";
                    newDiv.id = id;
                    let newContent = document.createTextNode(error);
                    newDiv.appendChild(newContent);
                    document.querySelector("body").append(newDiv);
                });
            ';
        $this->logger->debug("[run script]:");
        $this->logger->debug($script, ['pre' => true]);
        $this->driver->executeScript($script);

        $this->waitForElement(\WebDriverBy::xpath('//*[@id="' . $id . '"]'), 20, false);
        $ext = $this->waitForElement(\WebDriverBy::xpath('//script[@id="' . $id . '"]'), 0, false);
        $this->saveResponse();

        if (!$ext) {
            $this->waitForElement(\WebDriverBy::xpath('//div[@id="' . $id . '"]'), 0, false);

            return null;
        }

        return $ext->getAttribute($id);
    }

    private function fillRedeemTrip($fields, $cabin): bool
    {
        $this->logger->notice(__METHOD__);
        $this->curl->SetInputValue("departurePoint", $this->ports[$fields['DepCode']]);
        $this->curl->SetInputValue("destinationPoint", $this->ports[$fields['ArrCode']]);
        $this->curl->SetInputValue("departInputDate", $fields['DepDate']);
        $this->curl->SetInputValue("oneWay", "true");

        $cabinNames = $this->curl->FindPreg('/data-cabinNames="([^"]+)" data-cabinCodes="[\w:]+"/');
        $cabinCodes = $this->curl->FindPreg('/data-cabinCodes="([^"]+)"/');

        if (empty($cabinNames) || empty($cabinNames)) {
            $this->sendNotification('check cabin codes');

            throw new \CheckException('other cabin codes', ACCOUNT_ENGINE_ERROR);
        }
        $cabinNames = explode(":", $cabinNames);
        $cabinCodes = explode(":", $cabinCodes);
        $cabins = array_combine($cabinNames, $cabinCodes);
        $this->logger->notice(var_export($cabin, true));
        $this->logger->notice(var_export($cabins, true));

        if (!isset($cabins[$fields['Cabin']]) && !isset($cabins[$cabin])) {
            $this->logger->notice('change cabin');

            if ($fields['Cabin'] === 'Premium economy') {
                $fields['Cabin'] = 'Economy';
            }
        }

        if (!isset($cabins[$fields['Cabin']]) && !isset($cabins[$cabin])) {
            return false;
        }
        $this->curl->SetInputValue("CabinCode", $cabins[$fields['Cabin']] ?? $cabins[$cabin]);
        $this->curl->SetInputValue("NumberOfAdults", $fields['Adults']);
        $this->curl->SetInputValue("NumberOfYoungAdults", 0);
        $this->curl->SetInputValue("NumberOfChildren", 0);
        $this->curl->SetInputValue("NumberOfInfants", 0);
        $this->curl->Form['DEVICE_TYPE'] = 'DESKTOP';

        if (isset($this->curl->Form['returnInputDate'])) {
            unset($this->curl->Form['returnInputDate']);
        }

        if (isset($this->curl->Form['upgradeInbound'])) {
            unset($this->curl->Form['upgradeInbound']);
        }

        if (isset($this->curl->Form['upgrade_redemption_type'])) {
            unset($this->curl->Form['upgrade_redemption_type']);
        }

        return true;
    }

    private function postRedeemTrip($isRetry, $fields, $cabin): void
    {
        $this->logger->notice(__METHOD__ . ' with isRetry=' . var_export($isRetry, true));

        if (!$this->curl->PostForm()) {
            $this->checkErrors();

            throw new \CheckException('error post plan_redeem_trip', ACCOUNT_ENGINE_ERROR);
        }

        if ($this->curl->Response['code'] == 403) {
            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->curl->FindPreg("/Validation question/") && $this->curl->ParseForm("captcha_form")) {
            $this->sendNotification('captcha on search (1) // ZM');

            throw new \CheckException('captcha', ACCOUNT_ENGINE_ERROR);
            $captcha = $this->parseCaptcha();

            if ($captcha === false) {
                throw new \CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
            $this->curl->SetInputValue('h-captcha-response', $captcha);

            if (!$this->curl->PostForm()) {
                throw new \CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }

            if ($this->curl->FindPreg("/Validation question/")
                && $this->curl->ParseForm("captcha_form")
                && $this->curl->FindSingleNode("//p[normalize-space(text())='You did not validate successfully. Please try again.']")
            ) {
                $this->captchaReporting($this->recognizer, false);
                $this->sendNotification('captcha on search (2) // ZM');

                throw new \CheckException('captcha', ACCOUNT_ENGINE_ERROR);
                $captcha = $this->parseCaptcha();

                if ($captcha === false) {
                    throw new \CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                }
                $this->curl->SetInputValue('h-captcha-response', $captcha);

                if (!$this->curl->PostForm()) {
                    throw new \CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
                }
            }
        }

        if (!$this->curl->ParseForm("SubmitFromInterstitial")) {
            if ($this->curl->FindSingleNode("//li[contains(.,\"It looks like you're using multiple tabs in the same browser. Please use a single tab to continue.\")]")) {
                if (!$this->curl->ParseForm("plan_redeem_trip")) {
                    $this->logger->error('check parse');

                    if ($msg = $this->curl->FindSingleNode("//text()[contains(.,'Sorry, there seems to be a technical problem. Please try again in a few minutes')]/ancestor::*[1]")) {
                        $this->logger->error($msg);

                        throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                    }

                    throw new \CheckException('not load plan_redeem_trip', ACCOUNT_ENGINE_ERROR);
                }

                if (!$isRetry && $this->fillRedeemTrip($fields, $cabin)) {
                    $this->postRedeemTrip(true, $fields, $cabin);

                    return;
                }
                $this->logger->notice('failed retry, try restart');

                throw new \CheckRetryNeededException(5, 0);
            }

            if ($this->curl->FindSingleNode("//p[contains(normalize-space(),'You did not validate successfully. Please try again')]")) {
                // TODO m/b it's better retry
                throw new \CheckRetryNeededException(5, 0);
            }
            $this->logger->error('check parse');

            if ($msg = $this->curl->FindSingleNode("//text()[contains(.,'Sorry, there seems to be a technical problem. Please try again in a few minutes')]/ancestor::*[1]")) {
                $this->logger->error($msg);

                if ($this->attempt == 0) {
                    throw new \CheckRetryNeededException(5, 0);
                }

                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
            }

            throw new \CheckException('no form SubmitFromInterstitial', ACCOUNT_ENGINE_ERROR);
        }
        $eventId = $this->curl->FindPreg("/var eventId = '(\d+)';/");

        if (empty($eventId)) {
            $this->logger->error('can\'t find eventId');

            throw new \CheckException('can\'t find eventId', ACCOUNT_ENGINE_ERROR);
        }
        $this->curl->SetInputValue("eId", $eventId);

        if (!$this->curl->PostForm()) {
            $this->checkErrors();
            $this->logger->error('error post SubmitFromInterstitial');

            throw new \CheckRetryNeededException(5, 0);
        }

        if ($this->curl->FindPreg("/Validation question/") && $this->curl->ParseForm("captcha_form")) {
            $this->sendNotification('captcha on search (3) // ZM');

            throw new \CheckException('captcha', ACCOUNT_ENGINE_ERROR);
            $captcha = $this->parseCaptcha();

            if ($captcha === false) {
                throw new \CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
            $this->curl->SetInputValue('g-recaptcha-response', $captcha);

            if (!$this->curl->PostForm()) {
                throw new \CheckException(self::CAPTCHA_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
            }
            $eventId = $this->curl->FindPreg("/var eventId = '(\d+)';/");

            if ($this->curl->ParseForm("SubmitFromInterstitial")) {
                if (empty($eventId)) {
                    $this->logger->error('can\'t find eventId (2)');

                    throw new \CheckException('can\'t find eventId', ACCOUNT_ENGINE_ERROR);
                }

                if (!$this->curl->PostForm()) {
                    $this->checkErrors();
                    $this->logger->error('error post SubmitFromInterstitial');

                    throw new \CheckRetryNeededException(5, 0);
                }
            }

            if ($this->curl->FindPreg("/Validation question/") && $this->curl->ParseForm("captcha_form")) {
                throw new \CheckException('walks in a circle', ACCOUNT_ENGINE_ERROR);
            }
        }

        if ($this->curl->FindSingleNode('//select[@name="departureStopoverPoint"]')) {
            $this->getFlightsWithStops($fields, $fields['DepDate']);
            //date('m/d/y', strtotime('+1 day', $this->depDateIn));
        }

        if ($this->curl->ParseForm("plan_trip")) {
            if ($this->curl->Form['departurePoint'] !== $this->ports[$fields['DepCode']]
                || $this->curl->Form['destinationPoint'] !== $this->ports[$fields['ArrCode']]
                || $this->curl->Form['departInputDate'] !== $fields['DepDate']
            ) {
                // sometimes captcha broke request, fix it

                //hardcode TEST
                $this->curl->Form['departurePoint'] = $fields['DepCode'];
                $this->curl->Form['destinationPoint'] = $fields['ArrCode'];
                $this->curl->Form['stopoverOptions'] = "YES";
                $this->curl->Form['departInputDate'] = $fields['DepDate'];
                $this->curl->Form['departureStopoverPoint'] = "LON";
                $this->curl->Form['stopOverDepartInputDate'] = $fields['DepDate'];
                $this->curl->Form['display'] = "Continue";
            }
            // no stopovers
            if (!$this->curl->PostForm()) {
                $this->checkErrors();

                throw new \CheckException('error post plan_trip', ACCOUNT_ENGINE_ERROR);
            }

            if ($this->curl->ParseForm("SubmitFromInterstitial")) {
                $eventId = $this->curl->FindPreg("/var eventId = '(\d+)';/");

                if (empty($eventId)) {
                    $this->logger->error('can\'t find eventId');

                    throw new \CheckException('can\'t find eventId', ACCOUNT_ENGINE_ERROR);
                }
                $this->curl->SetInputValue("eId", $eventId);

                if (!$this->curl->PostForm()) {
                    $this->checkErrors();
                    $this->logger->error('error post SubmitFromInterstitial');

                    throw new \CheckRetryNeededException(5, 0);
                }
            }
        }

        if ($this->curl->ParseForm("stopOverForm")) {
            // no stopovers
            if (!$this->curl->PostForm()) {
                $this->checkErrors();

                throw new \CheckException('error post stopOverForm', ACCOUNT_ENGINE_ERROR);
            }

            if ($this->curl->ParseForm("SubmitFromInterstitial")) {
                $this->logger->error('check parse');
                $eventId = $this->curl->FindPreg("/var eventId = '(\d+)';/");

                if (empty($eventId)) {
                    $this->logger->error('can\'t find eventId');

                    throw new \CheckException('can\'t find eventId', ACCOUNT_ENGINE_ERROR);
                }
                $this->curl->SetInputValue("eId", $eventId);

                if (!$this->curl->PostForm()) {
                    $this->checkErrors();
                    $this->logger->error('error post SubmitFromInterstitial');

                    throw new \CheckRetryNeededException(5, 0);
                }
            }
        }

        if ($this->curl->ParseForm("plan_redeem_trip") && $this->curl->FindSingleNode("//li[normalize-space()='Book with Avios']")
            && !$this->curl->FindSingleNode("//div[@id='blsErrors']")
        ) {
            if (!$isRetry && $this->fillRedeemTrip($fields, $cabin)) {
                $this->postRedeemTrip(true, $fields, $cabin);

                return;
            }

            throw new \CheckRetryNeededException(5, 0);
        }
    }

    private function parseRewardFlights($fields, $cabinIn): array
    {
        $countryCode = $this->getCountryCode();

        // fix XPath
        $this->curl->FilterHTML = false;
        $this->curl->SetBody($this->curl->Response['body'] = preg_replace("#\b(ns|xmlns)\b=\"[^\"]+\"#i", '',
            $this->curl->Response['body']));

        $routes = [];
        // if no filter by cabin
        $xpathAllCabins = "//div[contains(@class,'flight-cabin-detail')][./span[contains(@class,'travel-class')]/following-sibling::div[1][not(contains(.,'Not Available') or contains(.,'Cabin not operated on this flight'))]]";

        // if filter by cabin
//        $xpath = "//div[contains(@class,'flight-cabin-detail')][./span[contains(@class,'travel-class')][normalize-space()='{$cabinIn}']/following-sibling::div[1][not(contains(.,'Not Available') or contains(.,'Cabin not operated on this flight'))]]";
        $xpath = "//button[contains(@class,'flight-cabin-detail')][./span[contains(@class,'travel-class')][normalize-space()='{$cabinIn}']/following-sibling::div[1][not(contains(.,'Not Available') or contains(.,'Cabin not operated on this flight'))]]";

        // if filter by exclude selected cabin

//        $xpathExclude = "//div[contains(@class,'flight-cabin-detail')][./span[contains(@class,'travel-class')][normalize-space()!='{$cabinIn}']/following-sibling::div[1][not(contains(.,'Not Available') or contains(.,'Cabin not operated on this flight'))]]";
        $xpathExclude = "//button[contains(@class,'flight-cabin-detail')][./span[contains(@class,'travel-class')][normalize-space()!='{$cabinIn}']/following-sibling::div[1][not(contains(.,'Not Available') or contains(.,'Cabin not operated on this flight'))]]";

        $Roots = $this->curl->XPath->query($rootStr = "//article[@id='article_0']//div[@class='direct-flight-details' or @class='conn-flight-details'][.{$xpath}]");
        $this->logger->debug("Found {$Roots->length} routes");
        $this->logger->warning($rootStr);

        if ($Roots->length === 0 && $this->curl->FindSingleNode("//li[@data-date-value='{$this->inDateDep}000']/a/span[normalize-space()='No availability']")) {
            $this->SetWarning('No availability');

            return [];
        }
        $formToken = null;
        $flightDetails = [];

        // check date of result
        $segmentsRoot_0 = $this->curl->XPath->query(".//div[contains(@class,'travel-time-detail')]", $Roots->item(0));

        if ($segmentsRoot_0->length > 0) {
            $r = $segmentsRoot_0->item(0);
            $date = $this->curl->FindSingleNode("./div[1]/descendant::p[contains(@class,'date')][1]", $r);
            $date1 = $this->curl->FindSingleNode("//li[contains(@class,'active-tab')]//span[contains(@class,'datemonth')]");
            $depDate = EmailDateHelper::parseDateRelative($date, strtotime('-1 day'));
            $depDate1 = EmailDateHelper::parseDateRelative($date1, strtotime('-1 day'));

            if ($depDate !== $this->depDateIn && $depDate1 !== $this->depDateIn) {
                $this->logger->debug('segment Date' . var_export($depDate, true));
                $this->logger->debug('checked Date' . var_export($depDate1, true));
                $this->logger->debug('input Date' . var_export($this->depDateIn, true));

                throw new \CheckException('wrong format: departInputDate', ACCOUNT_ENGINE_ERROR);
            }
        }
        $skippedPrice = null;
        $routesProblemWithBooking = $routesTechnicalProblem = [];

        if ($this->curl->FindSingleNode("//article[@id='article_1']")) {
            $wasBreak = $this->getInfoFinalSegment($Roots, $xpath, $fields, $countryCode, false, $routes, $formToken,
                $skippedPrice, $flightDetails, $routesProblemWithBooking, $routesTechnicalProblem);
        } else {
            $wasBreak = $this->collectRoutes($Roots, $xpath, $fields, $countryCode, false, $routes, $formToken,
                $skippedPrice, $flightDetails, $routesProblemWithBooking, $routesTechnicalProblem);
        }

        if ($wasBreak) {
            return $routes;
        }

        if ((time() - $this->requestDateTime) < $this->AccountFields['Timeout'] || empty($routes)) {
            $this->logger->warning('collect flights with other cabins');
            $Roots = $this->curl->XPath->query($rootStr = "//article[@id='article_0']//div[@class='direct-flight-details' or @class='conn-flight-details'][.{$xpathExclude}]");
            $this->logger->debug("Found {$Roots->length} routes");
            $this->logger->warning($rootStr);

            if (empty($routes) && $Roots->length > 0) {
                $segmentsRoot_0 = $this->curl->XPath->query(".//div[contains(@class,'travel-time-detail')]",
                    $Roots->item(0));
                $r = $segmentsRoot_0->item(0);
                $date = $this->curl->FindSingleNode("./div[1]/descendant::p[contains(@class,'date')][1]", $r);
                $depDate = EmailDateHelper::parseDateRelative($date, strtotime('-1 day'));

                if ($depDate !== $this->depDateIn) {
                    $this->logger->debug(var_export($depDate, true));
                    $this->logger->debug(var_export($this->depDateIn, true));

                    throw new \CheckException('wrong format: departInputDate', ACCOUNT_ENGINE_ERROR);
                }
            }

            if ($this->curl->FindSingleNode("//article[@id='article_1']")) {
                $wasBreak = $this->getInfoFinalSegment($Roots, $xpath, $fields, $countryCode, false, $routes,
                    $formToken, $skippedPrice, $flightDetails, $routesProblemWithBooking, $routesTechnicalProblem);
            } else {
                $wasBreak = $this->collectRoutes($Roots, $xpath, $fields, $countryCode, false, $routes, $formToken,
                    $skippedPrice, $flightDetails, $routesProblemWithBooking, $routesTechnicalProblem);
            }

            if ($wasBreak) {
                return $routes;
            }
        }
        $this->logger->debug('Parsed data:');
        $this->logger->debug(var_export($routes, true), ['pre' => true]);

        if (empty($routes)) {
            $this->SetWarning('No results');
        }

        $Roots = $this->curl->XPath->query("//div[@class='direct-flight-details' or @class='conn-flight-details'][.{$xpathAllCabins}]");

        if (isset($skippedPrice) && empty($routes) && $Roots->length) {
            $routesProblemWithBooking = array_unique($routesProblemWithBooking);

            if ($Roots->length === count($routesProblemWithBooking)) {
                throw new \CheckException('Sorry, there’s a problem with booking this journey online. You can try selecting other flights or contact us so an agent can help you book the flights you have selected', ACCOUNT_PROVIDER_ERROR);
            }

            $routesTechnicalProblem = array_unique($routesTechnicalProblem);

            if ($Roots->length === count($routesTechnicalProblem)) {
                throw new \CheckException('Sorry, there seems to be a technical problem. Please try again in a few minutes', ACCOUNT_PROVIDER_ERROR);
            }

            throw new \CheckException("can't get prices", ACCOUNT_ENGINE_ERROR);
        }

        return $routes;
    }

    private function collectRoutes(
        $Roots,
        $xpath,
        $fields,
        $countryCode,
        $checkLimit,
        &$routes,
        &$formToken,
        &$skippedPrfice,
        &$flightDetails,
        &$routesProblemWithBooking,
        &$routesTechnicalProblem
    ): bool {
        $this->logger->notice(__METHOD__);

        foreach ($Roots as $numRoot => $root) {
            if ($checkLimit && !empty($routes) && (time() - $this->requestDateTime) > $this->AccountFields['Timeout']) {
                return false;
            }
            $result = ['connections' => []];

            $segmentsRoot = $this->curl->XPath->query(".//div[contains(@class,'travel-time-detail')]", $root);

            if ($segmentsRoot->length === 0) {
                $this->sendNotification("check segments");

                continue;
            }
            $offerList = $this->curl->XPath->query(".{$xpath}", $root);

            $offers = [];

            foreach ($offerList as $offer) {
                $cabinStr = $this->curl->FindSingleNode("./span[contains(@class,'travel-class')]", $offer);

                $cabinKey = $this->curl->FindSingleNode("./span[contains(@class,'travel-class')]/following-sibling::div[1]//div[contains(@id,'DtlOuterDivRadio')]/@id",
                    $offer, false, '/DtlOuterDivRadio(\d-\d-\w)\s*$/');

                $cabinSeg = $this->curl->FindNodes(".//div[contains(@class,'travel-time-detail')]//p[contains(@class,'cabinName')]/span[contains(@id,'cbnName{$cabinKey}')]",
                    $root);

                if (empty($cabinSeg)) {
                    $this->sendNotification('check cabins // ZM');
                }

                $cabin = array_search($cabinStr, $this->getCabinFields(false));

                if (empty($cabin)) {
                    $cabin = array_search(ucfirst(strtolower($cabinStr)), $this->getCabinFields(false));
                }
                $tickets = $this->curl->FindSingleNode(".//span[@class='message-number-of-seats']", $offer, false,
                    "/(\d+) left/i");

                $paramForOutbound = $this->curl->FindSingleNode(".//input[@name='0']/@value", $offer);
                $paramForInbound = '';
                $eId = $this->curl->FindSingleNode("//*[@id='hdnEIdSB']/@value");
                $stopover = json_encode($this->curl->FindSingleNode("//*[@id='Stopover']/@value") === 'true');
                $hostAirlineCode = $this->curl->FindSingleNode("//*[@id='HostAirlineCode']/@value");
                $BAOnlyRoute = $this->curl->FindSingleNode("//*[@id='BAOnlyRoute']/@value");
                $BANotOperateFullOrPartial = $this->curl->FindSingleNode("//*[@id='BANotOperateFullOrPartial']/@value");
                $hostAirline = $this->curl->FindSingleNode("//*[@id='HostAirline']/@value");

                if (!isset($formToken)) {
                    $formToken = $this->curl->FindSingleNode("//*[@id='formToken']/@value");
                }

                $http2 = clone $this->curl;
                $http2->FilterHTML = false;
                $this->curl->brotherBrowser($http2);

                $headers = [
                    'Accept'           => '*/*',
                    'Origin'           => 'https://www.britishairways.com',
                    'Referer'          => 'https://www.britishairways.com/travel/redeem/execclub/_gf/en_' . $countryCode,
                    'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With' => 'XMLHttpRequest',
                ];
                $x_dtpc = $http2->getCookieByName('dtPC');

                if (!empty($x_dtpc)) {
                    $headers['x-dtpc'] = $x_dtpc;
                }

                $zeroParam = $paramForOutbound . $paramForInbound;
                $payload = "0={$zeroParam}&eId={$eId}&&Stopover={$stopover}&&HostAirlineCode={$hostAirlineCode}&&BAOnlyRoute={$BAOnlyRoute}&&BANotOperateFullOrPartial={$BANotOperateFullOrPartial}&&HostAirline=$hostAirline&&FormToken={$formToken}";
                $http2->PostURL("https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$countryCode}/device-all",
                    $payload, $headers);

                if ($msg = $http2->FindSingleNode("//li[contains(.,'Sorry, there’s a problem with booking this journey online')] | //li[contains(.,'This flight sequence is not possible.')]")) {
                    $this->logger->error($msg);
                    $routesProblemWithBooking[] = $numRoot;
                    $noPrice = true;
                } elseif (empty($http2->FindSingleNode("//span[@class='totalPriceAviosTxt']"))
                    && $http2->FindSingleNode("//div[@name='cacheKeyList']/following-sibling::span[1]") === 'false') {
                    $formToken = $http2->FindSingleNode("//div[@name='totalPriceAvios']/@data-formToken");

                    if (empty($formToken)) {
                        $formToken = $http2->FindPreg("/name=\"totalPriceAvios\" data-formToken=\"(\d+)\">/");
                    }
                    $this->logger->debug('formToken for retry: ' . $formToken);

                    if (!empty($formToken)) {
                        $payload = "0={$zeroParam}&&eId={$eId}&&Stopover={$stopover}&&HostAirlineCode={$hostAirlineCode}&&BAOnlyRoute={$BAOnlyRoute}&&BANotOperateFullOrPartial={$BANotOperateFullOrPartial}&&HostAirline=$hostAirline&&FormToken={$formToken}";
                        sleep(2); //sometimes works
                        $http2->PostURL("https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$countryCode}/device-all",
                            $payload, $headers);
                    }

                    if ($http2->FindSingleNode("//div[@name='cacheKeyList']/following-sibling::span[1]") === 'false') {
                        $this->logger->error('empty price');
                        $noPrice = true;
                    }
                }

                $price = $http2->FindSingleNode("//span[@class='totalPriceAviosTxt']");

                if (empty($price) && ($msg = $http2->FindSingleNode("//p[starts-with(normalize-space(),'Sorry, there seems to be a technical problem. Please try again in a few minutes, and')]"))) {
                    sleep(2);
                    $payload = "0={$zeroParam}&&eId={$eId}&&Stopover={$stopover}&&HostAirlineCode={$hostAirlineCode}&&BAOnlyRoute={$BAOnlyRoute}&&BANotOperateFullOrPartial={$BANotOperateFullOrPartial}&&HostAirline=$hostAirline&&FormToken={$formToken}";
                    $http2->PostURL("https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$countryCode}/device-all",
                        $payload, $headers);
                    $price = $http2->FindSingleNode("//span[@class='totalPriceAviosTxt']");

                    if (empty($price)) {
                        $formToken = $http2->FindSingleNode("//div[@name='totalPriceAvios']/@data-formToken");

                        if (empty($formToken)) {
                            $formToken = $http2->FindPreg("/name=\"totalPriceAvios\" data-formToken=\"(\d+)\">/");
                        }

                        if (empty($formToken)) {
                            $this->logger->error($msg);
                            $this->logger->error('empty price');

                            if (!isset($prevToken)) {
                                if (count($routes) > 0) {
                                    return true;
                                }

                                throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                            }
                            $formToken = $prevToken;
                            $this->logger->debug("skip journey online");
                            $skippedPrice = true;
                            $routesTechnicalProblem[] = $numRoot;

                            continue;
                        }

                        $msg = $http2->FindSingleNode("//p[starts-with(normalize-space(),'Sorry, there seems to be a technical problem. Please try again in a few minutes, and')]");

                        if ($msg) {
                            $this->logger->error($msg);
                            $this->logger->debug("skip journey online");
                            $skippedPrice = true;
                            $routesTechnicalProblem[] = $numRoot;

                            continue;
                        }

                        if ($http2->FindSingleNode("//div[@name='cacheKeyList']/following-sibling::span[1]") !== 'false') {
                            $this->sendNotification('retry after technical problem on getPrice - helped // ZM');
                        }
                    }
                }
                $price = $http2->FindSingleNode("//span[@class='totalPriceAviosTxt']");

                $prevToken = $formToken;
                $formToken = $http2->FindSingleNode("//div[@name='totalPriceAvios']/@data-formToken");

                if (empty($formToken)) {
                    $formToken = $http2->FindPreg("/name=\"totalPriceAvios\" data-formToken=\"(\d+)\">/");
                }

                if (empty($formToken)) {
                    $this->logger->error('check formToken');

                    if (!empty($prevToken) && (!isset($tryWithPrevToken) || $tryWithPrevToken <= 2)) {
                        $tryWithPrevToken = isset($tryWithPrevToken) ? $tryWithPrevToken + 1 : 0;
                        $formToken = $prevToken;
                        $skippedPrice = true;
                    } elseif (count($routes) > 0 && isset($tryWithPrevToken) && $tryWithPrevToken > 2) {
                        return true;
                    } else {
                        throw new \CheckException('problem with formToken', ACCOUNT_ENGINE_ERROR);
                    }
                }

                if (isset($noPrice)) {
                    $skippedPrice = true;
                    $noPrice = null;
                    $this->logger->debug("skip journey online");

                    continue;
                }

                if (empty($price)) {
                    $this->logger->error('skip offer. price not found');

                    continue;
                }
                $offers[$cabin] = ['price' => $price, 'tickets' => $tickets, 'cabinSeg' => $cabinSeg];
            }
            $stop = -1;
            $price = null;
            $layover = null;
            $totalFlight = null;

            foreach ($segmentsRoot as $i => $r) {
                $stop++;

                $depTime = $this->curl->FindSingleNode("./div[1]/descendant::p[contains(@class,'time')][1]", $r);
                $date = $this->curl->FindSingleNode("./div[1]/descendant::p[contains(@class,'date')][1]", $r);
                $depDate = EmailDateHelper::parseDateRelative($date, strtotime('-1 day'));

                $arrTime = $this->curl->FindSingleNode("./div[2]/descendant::p[contains(@class,'time')][1]", $r);
                $date = $this->curl->FindSingleNode("./div[2]/descendant::p[contains(@class,'date')][1]", $r);
                $arrDate = EmailDateHelper::parseDateRelative($date, strtotime('-1 day'));

                $seg = [
                    'num_stops' => $this->curl->FindSingleNode("./div[3]//p[contains(.,'Stops')]", $r, false,
                            "/Stops:\s*(\d+)/") ?? 0,
                    'departure' => [
                        'date'     => date('Y-m-d H:i', strtotime($depTime, $depDate)),
                        'dateTime' => strtotime($depTime, $depDate),
                        'airport'  => $this->curl->FindSingleNode("./div[1]/div[@class='airport-box']/p[1]", $r),
                    ],
                    'arrival' => [
                        'date'     => date('Y-m-d H:i', strtotime($arrTime, $arrDate)),
                        'dateTime' => strtotime($arrTime, $arrDate),
                        'airport'  => $this->curl->FindSingleNode("./div[2]/div[@class='airport-box']/p[1]", $r),
                    ],
                    'cabin'   => null,
                    'flight'  => [$this->curl->FindSingleNode("./div[3]//a/span[2]", $r)],
                    'airline' => $this->curl->FindSingleNode("./div[3]//a/span[2]", $r, false,
                        '/^([A-Z\d]{2})\s*\d+$/'),
                    'distance' => null,
                ];

                $stop += $seg['num_stops'];
                $result['connections'][] = $seg;
            }

            foreach ($offers as $cabin => $data) {
                $fees = $this->curl->FindPreg("/\d+\s+Avios\s+\+\s+\D+?(\d[\d.,]+)$/", false, $data['price']);
                $currency = $this->curl->FindPreg("/\d+\s+Avios\s+\+\s+(\D+?)\d[\d.,]+$/", false, $data['price']);
                $this->logger->debug("parsed fees: " . $fees);
                $this->logger->debug("parsed currency: " . $currency);

                if ($currency === '¥') {
                    $currency = 'JPY';
                }
                $totalTravel = null;
                $headData = [
                    'distance'  => null,
                    'num_stops' => $stop,
                    'times'     => [
                        'flight'  => $totalTravel,
                        'layover' => $layover,
                    ],
                    'redemptions' => [
                        'miles' => intdiv($this->curl->FindPreg("/(\d+)\s+Avios/", false, $data['price']),
                            $fields['Adults']),
                        'program' => $this->AccountFields['ProviderCode'],
                    ],
                    'payments' => [
                        'currency' => $this->currency($currency),
                        'taxes'    => null,
                        'fees'     => round(\AwardWallet\Common\Parser\Util\PriceHelper::cost($fees) / $fields['Adults'],
                            2),
                    ],
                ];

                if (empty($headData['payments']['currency'])) {
                    // TODO может стоит ретрай с обновленным formToken
                    $this->logger->error('can\'t determine currency');
                    $this->DebugInfo = 'can\'t determine currency';

                    if (count($routes) > 0) {
                        return true;
                    }

                    throw new \CheckException('Something went wrong', ACCOUNT_ENGINE_ERROR);
                }

                $offerResult = $result;

                foreach ($result['connections'] as $i => $seg) {
                    if (isset($data['cabinSeg'][$i])) {
                        $offerResult['connections'][$i]['cabin'] = array_search($data['cabinSeg'][$i],
                            $this->getCabinFields(false));

                        if (empty($offerResult['connections'][$i]['cabin'])) {
                            $offerResult['connections'][$i]['cabin'] = $cabin;
                        }
//                            $offerResult['connections'][$i]['classOfService'] = $this->clearCOS($data['cabinSeg'][$i]);
                    } else {
                        $offerResult['connections'][$i]['cabin'] = $cabin;
//                            $offerResult['connections'][$i]['classOfService'] = $this->clearCOS($this->getCabinFields(false)[$cabin]);
                    }
                }
                $res = array_merge($headData, $offerResult);
                $res['tickets'] = $data['tickets'];
                $res['classOfService'] = $this->clearCOS($this->getCabinFields(false)[$cabin]);
                $this->logger->debug(var_export($res, true), ['pre' => true]);
                $routes[] = $res;
            }
        }

        return false;
    }

    private function clearCOS(string $cos): string
    {
        if (preg_match("/^(.+\w+) (?:class)$/i", $cos, $m)) {
            $cos = $m[1];
        }

        return $cos;
    }

    private function isBadProxy($http = null): bool
    {
        if (!isset($http)) {
            $http = $this->http;
        }

        return strpos($http->Error, 'Network error 28 - Connection timed out after') !== false
            || strpos($http->Error, 'Network error 56 - Received HTTP code 400 from proxy after CONNECT') !== false
            || strpos($http->Error, 'Network error 56 - Received HTTP code 403 from proxy after CONNECT') !== false
            || strpos($http->Error, 'Network error 56 - Received HTTP code 503 from proxy after CONNECT') !== false
            || strpos($http->Error, 'Network error 56 - Unexpected EOF') !== false
            || strpos($http->Error, 'Network error 0 -') !== false
            || strpos($http->Error, 'Network error 56 - Proxy CONNECT aborted') !== false
            || strpos($http->Error, 'Operation timed out after') !== false
            || $http->FindSingleNode("(
            //h1[contains(., 'This site can’t be reached')]
            | //h1[normalize-space()='Access Denied']
            | //span[contains(text(), 'This site can’t be reached')]
            | //span[contains(text(), 'This page isn’t working')]
            | //text()[contains(.,'An error occurred while processing your request')]/following-sibling::p[starts-with(normalize-space(),'Reference #')]
            | //p[contains(text(), 'There is something wrong with the proxy server, or the address is incorrect.')]
            )[1]");
    }

    private function getFlightsWithStops($fields, $deepDate)
    {
        $this->logger->notice(__METHOD__);

        $departureStopoverPoint = $this->curl->FindSingleNode('//select[@name="departureStopoverPoint"]/option[not(@selected)][1]/@value');

        $this->curl->ParseForm("plan_trip");
        $this->curl->Form['departurePoint'] = $fields['DepCode'];
        $this->curl->Form['destinationPoint'] = $fields['ArrCode'];
        $this->curl->Form['stopoverOptions'] = "YES";
        $this->curl->Form['departInputDate'] = $fields['DepDate'];
        $this->curl->Form['departureStopoverPoint'] = $departureStopoverPoint;
        $this->curl->Form['stopOverDepartInputDate'] = $deepDate;
        $this->curl->Form['display'] = "Continue";

        //date('m/d/y', strtotime('+1 day', $this->depDateIn));  // дальше на следующий день.
        if (!$this->curl->PostForm()) {
            $this->checkErrors();

            throw new \CheckException('error post plan_trip', ACCOUNT_ENGINE_ERROR);
        }

        if ($this->curl->ParseForm("SubmitFromInterstitial")) {
            $eventId = $this->curl->FindPreg("/var eventId = '(\d+)';/");

            if (empty($eventId)) {
                $this->logger->error('can\'t find eventId');

                throw new \CheckException('can\'t find eventId', ACCOUNT_ENGINE_ERROR);
            }
            $this->curl->SetInputValue("eId", $eventId);

            if (!$this->curl->PostForm()) {
                $this->checkErrors();
                $this->logger->error('error post SubmitFromInterstitial');

                throw new \CheckRetryNeededException(5, 0);
            }
        }
    }

    private function getInfoFinalSegment(
        $Roots,
        $xpath,
        $fields,
        $countryCode,
        $checkLimit,
        &$routes,
        &$formToken,
        &$skippedPrfice,
        &$flightDetails,
        &$routesProblemWithBooking,
        &$routesTechnicalProblem
    ): bool {
        $this->logger->notice(__METHOD__);

        $finalSegmentRoots = $this->curl->XPath->query("//article[@id='article_1']//div[@class='direct-flight-details' or @class='conn-flight-details'][.{$xpath}]");

        foreach ($Roots as $numRoot => $root) {
            if ($checkLimit && !empty($routes) && (time() - $this->requestDateTime) > $this->AccountFields['Timeout']) {
                return false;
            }

            if (empty($finalSegmentRoots)) {
                $finalSegmentRoots = [0 => null];
            }
            $segmentsRoot = $this->curl->XPath->query(".//div[contains(@class,'travel-time-detail')]", $root);

            if ($segmentsRoot->length === 0) {
                $this->sendNotification("check segments");

                continue;
            }
            $offerList = $this->curl->XPath->query(".{$xpath}", $root);

            $offers = [];

            foreach ($finalSegmentRoots as $finalRoot) {
                $finalSegmentsRoot = $this->curl->XPath->query(".//div[contains(@class,'travel-time-detail')]",
                    $finalRoot);
                $finalOfferList = $this->curl->XPath->query(".{$xpath}", $finalRoot);

                foreach ($finalOfferList as $finalOffer) {
                    foreach ($offerList as $offer) {
                        $cabinStr = $this->curl->FindSingleNode("./span[contains(@class,'travel-class')]", $offer);

                        $cabinKey = $this->curl->FindSingleNode("./span[contains(@class,'travel-class')]/following-sibling::div[1]//div[contains(@id,'DtlOuterDivRadio')]/@id",
                            $offer, false, '/DtlOuterDivRadio(\d-\d-\w)\s*$/');

                        $cabinSeg = $this->curl->FindNodes(".//div[contains(@class,'travel-time-detail')]//p[contains(@class,'cabinName')]/span[contains(@id,'cbnName{$cabinKey}')]",
                            $root);

                        if (empty($cabinSeg)) {
                            $this->sendNotification('check cabins // ZM');
                        }

                        $cabin = array_search($cabinStr, $this->getCabinFields(false));

                        if (empty($cabin)) {
                            $cabin = array_search(ucfirst(strtolower($cabinStr)), $this->getCabinFields(false));
                        }
                        $tickets = $this->curl->FindSingleNode(".//span[@class='message-number-of-seats']", $offer,
                            false,
                            "/(\d+) left/i");

                        $paramForOutbound = $this->curl->FindSingleNode(".//input[@name='0']/@value", $offer);
                        $twoParamForOutbound = $this->curl->FindSingleNode(".//input[@name='1']/@value", $finalOffer);
                        $paramForInbound = '';
                        $eId = $this->curl->FindSingleNode("//*[@id='hdnEIdSB']/@value");
                        $stopover = json_encode($this->curl->FindSingleNode("//*[@id='Stopover']/@value") === 'true');
                        $hostAirlineCode = $this->curl->FindSingleNode("//*[@id='HostAirlineCode']/@value");
                        $BAOnlyRoute = $this->curl->FindSingleNode("//*[@id='BAOnlyRoute']/@value");
                        $BANotOperateFullOrPartial = $this->curl->FindSingleNode("//*[@id='BANotOperateFullOrPartial']/@value");
                        $hostAirline = $this->curl->FindSingleNode("//*[@id='HostAirline']/@value");

                        if (!isset($formToken)) {
                            $formToken = $this->curl->FindSingleNode("//*[@id='formToken']/@value");
                        }

                        $http2 = clone $this->curl;
                        $http2->FilterHTML = false;
                        $this->curl->brotherBrowser($http2);

                        $headers = [
                            'Accept'           => '*/*',
                            'Origin'           => 'https://www.britishairways.com',
                            'Referer'          => 'https://www.britishairways.com/travel/redeem/execclub/_gf/en_' . $countryCode,
                            'Content-Type'     => 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With' => 'XMLHttpRequest',
                        ];
                        $x_dtpc = $http2->getCookieByName('dtPC');

                        if (!empty($x_dtpc)) {
                            $headers['x-dtpc'] = $x_dtpc;
                        }

                        $zeroParam = $paramForOutbound . $paramForInbound;

                        if (!empty($twoParamForOutbound)) {
                            $oneParam = "1={$twoParamForOutbound}&";
                        }

                        $payload = "0={$zeroParam}&{$oneParam}eId={$eId}&&Stopover={$stopover}&&HostAirlineCode={$hostAirlineCode}&&BAOnlyRoute={$BAOnlyRoute}&&BANotOperateFullOrPartial={$BANotOperateFullOrPartial}&&HostAirline=$hostAirline&&FormToken={$formToken}";
                        $http2->PostURL("https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$countryCode}/device-all",
                            $payload, $headers);

                        if ($msg = $http2->FindSingleNode("//li[contains(.,'Sorry, there’s a problem with booking this journey online')] | //li[contains(.,'This flight sequence is not possible.')]")) {
                            $this->logger->error($msg);
                            $routesProblemWithBooking[] = $numRoot;
                            $noPrice = true;
                        } elseif (empty($http2->FindSingleNode("//span[@class='totalPriceAviosTxt']"))
                            && $http2->FindSingleNode("//div[@name='cacheKeyList']/following-sibling::span[1]") === 'false') {
                            $formToken = $http2->FindSingleNode("//div[@name='totalPriceAvios']/@data-formToken");

                            if (empty($formToken)) {
                                $formToken = $http2->FindPreg("/name=\"totalPriceAvios\" data-formToken=\"(\d+)\">/");
                            }
                            $this->logger->debug('formToken for retry: ' . $formToken);

                            if (!empty($formToken)) {
                                if (!empty($twoParamForOutbound)) {
                                    $oneParam = "1={$twoParamForOutbound}&";
                                }

                                $payload = "0={$zeroParam}&{$oneParam}eId={$eId}&&Stopover={$stopover}&&HostAirlineCode={$hostAirlineCode}&&BAOnlyRoute={$BAOnlyRoute}&&BANotOperateFullOrPartial={$BANotOperateFullOrPartial}&&HostAirline=$hostAirline&&FormToken={$formToken}";
                                sleep(2); //sometimes works
                                $http2->PostURL("https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$countryCode}/device-all",
                                    $payload, $headers);
                            }

                            if ($http2->FindSingleNode("//div[@name='cacheKeyList']/following-sibling::span[1]") === 'false') {
                                $this->logger->error('empty price');
                                $noPrice = true;
                            }
                        }

                        $price = $http2->FindSingleNode("//span[@class='totalPriceAviosTxt']");

                        if (empty($price) && ($msg = $http2->FindSingleNode("//p[starts-with(normalize-space(),'Sorry, there seems to be a technical problem. Please try again in a few minutes, and')]"))) {
                            sleep(2);

                            if (!empty($twoParamForOutbound)) {
                                $oneParam = "1={$twoParamForOutbound}&";
                            }

                            $payload = "0={$zeroParam}&{$oneParam}eId={$eId}&&Stopover={$stopover}&&HostAirlineCode={$hostAirlineCode}&&BAOnlyRoute={$BAOnlyRoute}&&BANotOperateFullOrPartial={$BANotOperateFullOrPartial}&&HostAirline=$hostAirline&&FormToken={$formToken}";
                            $http2->PostURL("https://www.britishairways.com/travel/redeem/execclub/_gf/en_{$countryCode}/device-all",
                                $payload, $headers);
                            $price = $http2->FindSingleNode("//span[@class='totalPriceAviosTxt']");

                            if (empty($price)) {
                                $formToken = $http2->FindSingleNode("//div[@name='totalPriceAvios']/@data-formToken");

                                if (empty($formToken)) {
                                    $formToken = $http2->FindPreg("/name=\"totalPriceAvios\" data-formToken=\"(\d+)\">/");
                                }

                                if (empty($formToken)) {
                                    $this->logger->error($msg);
                                    $this->logger->error('empty price');

                                    if (!isset($prevToken)) {
                                        if (count($routes) > 0) {
                                            return true;
                                        }

                                        throw new \CheckException($msg, ACCOUNT_PROVIDER_ERROR);
                                    }
                                    $formToken = $prevToken;
                                    $this->logger->debug("skip journey online");
                                    $skippedPrice = true;
                                    $routesTechnicalProblem[] = $numRoot;

                                    continue 3;
                                }

                                $msg = $http2->FindSingleNode("//p[starts-with(normalize-space(),'Sorry, there seems to be a technical problem. Please try again in a few minutes, and')]");

                                if ($msg) {
                                    $this->logger->error($msg);
                                    $this->logger->debug("skip journey online");
                                    $skippedPrice = true;
                                    $routesTechnicalProblem[] = $numRoot;

                                    continue 3;
                                }

                                if ($http2->FindSingleNode("//div[@name='cacheKeyList']/following-sibling::span[1]") !== 'false') {
                                    $this->sendNotification('retry after technical problem on getPrice - helped // ZM');
                                }
                            }
                        }
                        $price = $http2->FindSingleNode("//span[@class='totalPriceAviosTxt']");

                        $prevToken = $formToken;
                        $formToken = $http2->FindSingleNode("//div[@name='totalPriceAvios']/@data-formToken");

                        if (empty($formToken)) {
                            $formToken = $http2->FindPreg("/name=\"totalPriceAvios\" data-formToken=\"(\d+)\">/");
                        }

                        if (empty($formToken)) {
                            $this->logger->error('check formToken');

                            if (!empty($prevToken) && (!isset($tryWithPrevToken) || $tryWithPrevToken <= 2)) {
                                $tryWithPrevToken = isset($tryWithPrevToken) ? $tryWithPrevToken + 1 : 0;
                                $formToken = $prevToken;
                                $skippedPrice = true;
                            } elseif (count($routes) > 0 && isset($tryWithPrevToken) && $tryWithPrevToken > 2) {
                                return true;
                            } else {
                                throw new \CheckException('problem with formToken', ACCOUNT_ENGINE_ERROR);
                            }
                        }

                        if (isset($noPrice)) {
                            $skippedPrice = true;
                            $noPrice = null;
                            $this->logger->debug("skip journey online");

                            continue 3;
                        }

                        if (empty($price)) {
                            $this->logger->error('skip offer. price not found');

                            continue 3;
                        }
                        $offers[$cabin] = [
                            'price'    => $price,
                            'tickets'  => $tickets,
                            'cabinSeg' => $cabinSeg,
                        ];
                    }
                }
                $stop = -1;
                $price = null;
                $layover = null;
                $totalFlight = null;

                $this->logger->debug(var_export($offers, true), ['pre' => true]);

                foreach ($segmentsRoot as $i => $r) {
                    $stop++;
                    $seg = null;

                    $depTime = $this->curl->FindSingleNode("./div[1]/descendant::p[contains(@class,'time')][1]", $r);
                    $date = $this->curl->FindSingleNode("./div[1]/descendant::p[contains(@class,'date')][1]", $r);
                    $depDate = EmailDateHelper::parseDateRelative($date, strtotime('-1 day'));

                    $arrTime = $this->curl->FindSingleNode("./div[2]/descendant::p[contains(@class,'time')][1]", $r);
                    $date = $this->curl->FindSingleNode("./div[2]/descendant::p[contains(@class,'date')][1]", $r);
                    $arrDate = EmailDateHelper::parseDateRelative($date, strtotime('-1 day'));

                    $seg = [
                        'num_stops' => $this->curl->FindSingleNode("./div[3]//p[contains(.,'Stops')]", $r, false,
                                "/Stops:\s*(\d+)/") ?? 0,
                        'departure' => [
                            'date'     => date('Y-m-d H:i', strtotime($depTime, $depDate)),
                            'dateTime' => strtotime($depTime, $depDate),
                            'airport'  => $this->curl->FindSingleNode("./div[1]/div[@class='airport-box']/p[1]", $r),
                        ],
                        'arrival' => [
                            'date'     => date('Y-m-d H:i', strtotime($arrTime, $arrDate)),
                            'dateTime' => strtotime($arrTime, $arrDate),
                            'airport'  => $this->curl->FindSingleNode("./div[2]/div[@class='airport-box']/p[1]", $r),
                        ],
                        'cabin'   => null,
                        'flight'  => [$this->curl->FindSingleNode("./div[3]//a/span[2]", $r)],
                        'airline' => $this->curl->FindSingleNode("./div[3]//a/span[2]", $r, false,
                            '/^([A-Z\d]{2})\s*\d+$/'),
                        'distance' => null,
                    ];

                    $stop += $seg['num_stops'];
                    $segments[] = $seg;
                }

                foreach ($finalSegmentsRoot as $j => $fr) {
                    $stop++;
                    $seg = null;

                    $depTime = $this->curl->FindSingleNode("./div[1]/descendant::p[contains(@class,'time')][1]", $fr);
                    $date = $this->curl->FindSingleNode("./div[1]/descendant::p[contains(@class,'date')][1]", $fr);
                    $depDate = EmailDateHelper::parseDateRelative($date, strtotime('-1 day'));

                    $arrTime = $this->curl->FindSingleNode("./div[2]/descendant::p[contains(@class,'time')][1]", $fr);
                    $date = $this->curl->FindSingleNode("./div[2]/descendant::p[contains(@class,'date')][1]", $fr);
                    $arrDate = EmailDateHelper::parseDateRelative($date, strtotime('-1 day'));

                    $seg = [
                        'num_stops' => $this->curl->FindSingleNode("./div[3]//p[contains(.,'Stops')]", $fr, false,
                                "/Stops:\s*(\d+)/") ?? 0,
                        'departure' => [
                            'date'     => date('Y-m-d H:i', strtotime($depTime, $depDate)),
                            'dateTime' => strtotime($depTime, $depDate),
                            'airport'  => $this->curl->FindSingleNode("./div[1]/div[@class='airport-box']/p[1]", $fr),
                        ],
                        'arrival' => [
                            'date'     => date('Y-m-d H:i', strtotime($arrTime, $arrDate)),
                            'dateTime' => strtotime($arrTime, $arrDate),
                            'airport'  => $this->curl->FindSingleNode("./div[2]/div[@class='airport-box']/p[1]", $fr),
                        ],
                        'cabin'   => null,
                        'flight'  => [$this->curl->FindSingleNode("./div[3]//a/span[2]", $fr)],
                        'airline' => $this->curl->FindSingleNode("./div[3]//a/span[2]", $fr, false,
                            '/^([A-Z\d]{2})\s*\d+$/'),
                        'distance' => null,
                    ];

                    $stop += $seg['num_stops'];
                    $segments[] = $seg;
                }

                $this->logger->debug(var_export($segments, true), ['pre' => true]);

                foreach ($offers as $cabin => $data) {
                    $fees = $this->curl->FindPreg("/\d+\s+Avios\s+\+\s+\D+?(\d[\d.,]+)$/", false, $data['price']);
                    $currency = $this->curl->FindPreg("/\d+\s+Avios\s+\+\s+(\D+?)\d[\d.,]+$/", false, $data['price']);
                    $this->logger->debug("parsed fees: " . $fees);
                    $this->logger->debug("parsed currency: " . $currency);

                    if ($currency === '¥') {
                        $currency = 'JPY';
                    }
                    $totalTravel = null;
                    $headData = [
                        'distance'  => null,
                        'num_stops' => $stop,
                        'times'     => [
                            'flight'  => $totalTravel,
                            'layover' => $layover,
                        ],
                        'redemptions' => [
                            'miles' => intdiv($this->curl->FindPreg("/(\d+)\s+Avios/", false, $data['price']),
                                $fields['Adults']),
                            'program' => $this->AccountFields['ProviderCode'],
                        ],
                        'payments' => [
                            'currency' => $this->currency($currency),
                            'taxes'    => null,
                            'fees'     => round(\AwardWallet\Common\Parser\Util\PriceHelper::cost($fees) / $fields['Adults'],
                                2),
                        ],
                    ];

                    if (empty($headData['payments']['currency'])) {
                        // TODO может стоит ретрай с обновленным formToken
                        $this->logger->error('can\'t determine currency');
                        $this->DebugInfo = 'can\'t determine currency';

                        if (count($routes) > 0) {
                            return true;
                        }

                        throw new \CheckException('Something went wrong', ACCOUNT_ENGINE_ERROR);
                    }

                    $offerResult['connections'] = $segments;

                    foreach ($segments as $i => $seg) {
                        if (isset($data['cabinSeg'][$i])) {
                            $offerResult['connections'][$i]['cabin'] = array_search($data['cabinSeg'][$i],
                                $this->getCabinFields(false));

                            if (empty($offerResult['connections'][$i]['cabin'])) {
                                $offerResult['connections'][$i]['cabin'] = $cabin;
                            }
                        } else {
                            $offerResult['connections'][$i]['cabin'] = $cabin;
                        }
                    }
                    $res = array_merge($headData, $offerResult);
                    $res['tickets'] = $data['tickets'];
                    $res['classOfService'] = $this->clearCOS($this->getCabinFields(false)[$cabin]);
                    $this->logger->debug(var_export($res, true), ['pre' => true]);
                    $routes[] = $res;

                    $segments = null;
                }
            }
        }

        return false;
    }

    private function getGoogleAuthCode($secret): string
    {
        $this->logger->notice(__METHOD__);

        $time = new \DateTimeImmutable();

        if ($time instanceof \DateTimeInterface) {
            $timeForCode = floor($time->getTimestamp() / 30);
        } else {
            @trigger_error(
                'Passing anything other than null or a DateTimeInterface to $time is deprecated as of 2.0 ' .
                'and will not be possible as of 3.0.',
                \E_USER_DEPRECATED
            );
            $timeForCode = $time;
        }

        $secret = $this->decode($secret);

        $timeForCode = str_pad(pack('N', $timeForCode), 8, \chr(0), \STR_PAD_LEFT);

        $hash = hash_hmac('sha1', $timeForCode, $secret, true);
        $offset = \ord(substr($hash, -1));
        $offset &= 0xF;

        $truncatedHash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;

        return str_pad((string) ($truncatedHash % (10 ** 6)), 6, '0', \STR_PAD_LEFT);
    }

    private function decode($encodedString, $caseSensitive = true, $strict = false): string
    {
        if (!$encodedString || !\is_string($encodedString)) {
            return '';
        }

        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bitsPerCharacter = 5;
        $radix = 1 << $bitsPerCharacter;
        $rightPadFinalBits = true;
        $padCharacter = '=';

        $charmap = [];

        for ($i = 0; $i < $radix; ++$i) {
            $charmap[$chars[$i]] = $i;
        }

        $lastNotatedIndex = \strlen($encodedString) - 1;

        $this->logger->error($encodedString);

        while ($encodedString[$lastNotatedIndex] === $padCharacter[0]) {
            $encodedString = substr($encodedString, 0, $lastNotatedIndex);
            --$lastNotatedIndex;
        }
        $this->logger->error('before: ' . $encodedString);

        $rawString = '';
        $byte = 0;
        $bitsWritten = 0;

        for ($c = 0; $c <= $lastNotatedIndex; ++$c) {
            if (!isset($charmap[$encodedString[$c]]) && !$caseSensitive) {
                if (isset($charmap[$cUpper = strtoupper($encodedString[$c])])) {
                    $charmap[$encodedString[$c]] = $charmap[$cUpper];
                } elseif (isset($charmap[$cLower = strtolower($encodedString[$c])])) {
                    $charmap[$encodedString[$c]] = $charmap[$cLower];
                }
            }

            if (isset($charmap[$encodedString[$c]])) {
                $bitsNeeded = 8 - $bitsWritten;
                $unusedBitCount = $bitsPerCharacter - $bitsNeeded;

                if ($bitsNeeded > $bitsPerCharacter) {
                    $newBits = $charmap[$encodedString[$c]] << $bitsNeeded
                        - $bitsPerCharacter;
                    $bitsWritten += $bitsPerCharacter;
                } elseif ($c !== $lastNotatedIndex || $rightPadFinalBits) {
                    $newBits = $charmap[$encodedString[$c]] >> $unusedBitCount;
                    $bitsWritten = 8;
                } else {
                    $newBits = $charmap[$encodedString[$c]];
                    $bitsWritten = 8;
                }

                $byte |= $newBits;

                if (8 === $bitsWritten || $c === $lastNotatedIndex) {
                    $rawString .= pack('C', $byte);

                    if ($c !== $lastNotatedIndex) {
                        $bitsWritten = $unusedBitCount;
                        $byte = ($charmap[$encodedString[$c]]
                                ^ ($newBits << $unusedBitCount)) << 8 - $bitsWritten;
                    }
                }
            } elseif ($strict) {
                return "null";
            }
        }

        return $rawString;
    }

    private function hashToInt(string $bytes, int $start): int
    {
        return unpack('N', substr(substr($bytes, $start), 0, 4))[1];
    }
}
