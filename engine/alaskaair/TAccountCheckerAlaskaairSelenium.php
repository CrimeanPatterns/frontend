<?php

use AwardWallet\Common\Parsing\LuminatiProxyManager\Port;
use AwardWallet\Common\Parsing\Web\Proxy\Provider\MountRotatingRequest;
use AwardWallet\Engine\ProxyList;
use AwardWallet\Common\Parser\Util\PriceHelper;
use AwardWallet\Common\Parsing\Html;

class TAccountCheckerAlaskaairSelenium extends TAccountChecker
{
    use ProxyList;
    use SeleniumCheckerHelper;
    use PriceTools;
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    private $trips = null;
    private $history = [];

    public static function FormatBalance($fields, $properties)
    {
        if (isset($properties['SubAccountCode'], $properties['Discount'])
            && strstr($properties['SubAccountCode'], 'AlaskaairDiscountCodes')) {
            return $properties['Discount'];
        }

        if (isset($properties['SubAccountCode']) && strstr($properties['SubAccountCode'], 'alaskaairMyWallet')) {
            return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "$%0.2f");
        }

        return parent::FormatBalance($fields, $properties);
    }

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        /* =============================== */
        // prevent servers overloaded if authorizations is down
        $this->http->RetryCount = 1;
        /* =============================== */
//        $this->http->SetProxy($this->proxyWhite(), false);
        $this->setProxyBrightData();
        // using lpm to track traffic usage
        /*
        $this->requestProxyManager(new MountRotatingRequest());
        */
        $this->UseSelenium();
        $this->http->saveScreenshots = true;

        $this->useChromePuppeteer();
        $this->seleniumOptions->addAntiCaptchaExtension = true;

        $this->seleniumOptions->recordRequests = true;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.alaskaair.com/www2/ssl/myalaskaair/MyAlaskaAir.aspx?CurrentForm=UCSignInStart');
        $login = $this->waitForElement(WebDriverBy::xpath('//input[@name="username"]'), 10);

        if (
            !$login
            && ($captcha = $this->parseCaptcha())
            && ($answer = $this->waitForElement(WebDriverBy::xpath('//input[@name = "answer"]'), 0))
        ) {
            $this->saveResponse();
            $answer->sendKeys($captcha);
            $capSubmit = $this->waitForElement(WebDriverBy::xpath('//button[@id = "capSubmit"]'), 0);
            $capSubmit->click();

            $login = $this->waitForElement(WebDriverBy::xpath('//input[@name = "username"]'), 10);

            // selenium bug workaround
            if (
                !$login
                && ($capSubmit = $this->waitForElement(WebDriverBy::xpath('//button[@id = "capSubmit"]'), 0))
                && !$this->waitForElement(WebDriverBy::xpath('//p[contains(text(), "Enter the characters seen in the image below:")]'), 0)
            ) {
                $this->saveResponse();
                $capSubmit->click();

                $login = $this->waitForElement(WebDriverBy::xpath('//input[@name = "username"]'), 10);
            }

            if (!$login && $this->waitForElement(WebDriverBy::xpath('//div[contains(text(), "Incorrect CAPTCHA")]'), 0)) {
                $this->captchaReporting($this->recognizer, false);

                throw new CheckRetryNeededException(3, 0);
            }
        }

        $pass = $this->waitForElement(WebDriverBy::xpath('//input[@name="password"]'), 0);
        $btn = $this->waitForElement(WebDriverBy::xpath('//button[@data-action-button-primary="true"]'), 0);
        $this->saveResponse();
        $alaskaair = $this->getAlaskaair();
        $this->logger->debug("[Current URL]: {$this->http->currentUrl()}");

        if (!$login || !$pass || !$btn) {
            if ($this->loginSuccessful()) {
                return true;
            }

            if (!$login && $pass && $btn) {
                throw new CheckRetryNeededException(3, 0);
            }

            if ($this->http->currentUrl() == 'https://www.alaskaair.com/content/page-not-found?aspxerrorpath=/account/login') {
                $this->DebugInfo = "login page-not-found";
                throw new CheckRetryNeededException(3);
            }

            if ($this->http->FindSingleNode('//p[contains(text(), "Enter the characters seen in the image below:")]')) {
                $this->DebugInfo = "captcha";
                $this->markProxyAsInvalid();
                return false;
            }

            if ($this->http->FindSingleNode('//span[contains(text(), "This site can’t be reached") or contains(text(), "This page isn’t working")] | //span[contains(text(), "Oops, something went wrong.")]')) {
                $this->markProxyAsInvalid();
                throw new CheckRetryNeededException(3, 0);
            }

            return $alaskaair->checkErrors();
        }

        $login->sendKeys($this->AccountFields['Login']);
        $pass->sendKeys($this->AccountFields['Pass']);
        $this->saveResponse();

        $this->waitForElement(WebDriverBy::xpath('//a[contains(text(), "Solving is in process...")]'), 10);

        $this->waitFor(function () {
            $this->logger->warning("Solving is in process...");
            sleep(3);
            $this->saveResponse();

            return !$this->http->FindSingleNode('//a[contains(text(), "Solving is in process...")]');
        }, 250);

        $btn->click();

        $this->waitForElement(WebDriverBy::xpath('//a[contains(text(), "Solving is in process...")]'), 10);

        $this->waitFor(function () {
            $this->logger->warning("Solving is in process...");
            sleep(3);
            $this->saveResponse();

            return !$this->http->FindSingleNode('//a[contains(text(), "Solving is in process...")]');
        }, 250);

        if ($this->http->FindSingleNode('//a[contains(text(), "Solved")]')) {
            if ($btn = $this->waitForElement(WebDriverBy::xpath('//button[@data-action-button-primary="true"]'), 0)) {
                $this->logger->notice(">>> send login form one more time <<<");
                $pass = $this->waitForElement(WebDriverBy::xpath('//input[@name="password"]'), 0);
                $pass->clear();
                $pass->sendKeys($this->AccountFields['Pass']);

                $this->saveResponse();
                $btn->click();
            }
        }

        return true;
    }

    public function parseCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $img = $this->waitForElement(WebDriverBy::xpath("//div[@id = 'captchaContainer']"), 0);

        if (!$img) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;
        $pathToScreenshot = $this->takeScreenshotOfElement($img);
        $captcha = $this->recognizeCaptcha($this->recognizer, $pathToScreenshot);
        unlink($pathToScreenshot);

        return $captcha;
    }

    function Login()
    {
        $this->waitForElement(WebDriverBy::xpath('//div[@id = "prompt-alert"] | //div[@class = "mp-info__links"] | //h2[contains(text(), "Hi, ")] | //span[@class = "ulp-input-error-message"] | //h2[contains(text(), "The request is blocked")] | //div[contains(@class, "container") and contains(., "Unable to retrieve your Loyalty Information.")] | //span[contains(text(), "navbar-greeting-name") and contains(@class, "populate-display-name")] | //h3[contains(text(), "We are sorry, an error occurred.")] | //span[contains(text(), "This site can’t be reached") or contains(text(), "This page isn’t working")]'), 30);
        $this->saveResponse();

        $alaskaair = $this->getAlaskaair();

        if ($this->loginSuccessful()) {
            return true;
        }

        if ($message = $this->http->FindSingleNode('//div[@id = "prompt-alert"] | //span[@class = "ulp-input-error-message"]')) {
            $this->logger->error("[Error]: {$message}");

            if (strstr($message, 'The sign-in information entered does not match our records.')) {
                throw new CheckException($message, ACCOUNT_INVALID_PASSWORD);
            }

            if (strstr($message, 'Your account has been blocked after multiple consecutive login attempts')) {
                throw new CheckException($message, ACCOUNT_LOCKOUT);
            }

            if (
                $message == 'Something went wrong, please try again later'
                || $message == 'We are sorry, something went wrong when attempting to log in'
            ) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }

            $this->DebugInfo = $message;

            return false;
        }

        if (
            // AccountID: 4374028
            $this->http->FindSingleNode('
                //span[b[contains(text(), "Password Protected")] and contains(., "The Mileage Program number is password protected and not available for viewing activity online.")]
                | //div[contains(text(), "Mileage Program Discrepancies") and contains(., "The Mileage Program account has some discrepancies.")]
            ')
        ) {
            return true;
        }

        if ($this->http->FindSingleNode('//h2[contains(text(), "The request is blocked")] | //span[contains(text(), "This site can’t be reached") or contains(text(), "This page isn’t working")]')) {
            $this->markProxyAsInvalid();
            throw new CheckRetryNeededException(3);
        }

        if ($message = $this->http->FindSingleNode('//h3[contains(text(), "We are sorry, an error occurred.")]')) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        if ($this->http->FindSingleNode('//div[contains(@class, "container") and contains(., "Unable to retrieve your Loyalty Information.")]')) {
            throw new CheckException("Unable to retrieve your Loyalty Information.", ACCOUNT_PROVIDER_ERROR);
        }

        return $alaskaair->checkErrors();
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);

        if (
            $this->http->FindSingleNode('//div[@class = "mp-info__links"]')
            || $this->http->FindNodes('//*[self::h2 or self::span][contains(text(), "Hi, ")]')
            || $this->http->FindSingleNode('//span[contains(text(), "navbar-greeting-name") and contains(@class, "populate-display-name")]')
        ) {
            return true;
        }

        return false;
    }

    function Parse()
    {
        $this->http->GetURL("https://www.alaskaair.com/trips");
        $this->waitForElement(WebDriverBy::xpath('//h2[@id = "upcoming-header"]'), 10);
        $this->saveResponse();

        // stupid provider bug fix
        $this->http->GetURL("https://www.alaskaair.com/account/overview");
        $name = $this->waitForElement(WebDriverBy::xpath('//span[contains(@class, "mp-info__name")]'), 10);
        $this->saveResponse();

        if (!$name) {
            $this->logger->notice("[Current URL]: {$this->http->currentUrl()}");

            $this->http->GetURL("https://www.alaskaair.com/account/overview");
            $this->waitForElement(WebDriverBy::xpath('//span[contains(@class, "mp-info__name")] | //strong[contains(text(), "Looks like we are experiencing a temporary technical issue.")]'), 10);
            $this->saveResponse();

            $this->logger->notice("[Current URL]: {$this->http->currentUrl()}");

            if ($this->http->FindSingleNode('//strong[contains(text(), "Looks like we are experiencing a temporary technical issue.")]')) {
                throw new CheckRetryNeededException(2, 0);
            }
        }

        $alaskaair = $this->getAlaskaair();

        $seleniumDriver = $this->http->driver;
        $requests = $seleniumDriver->browserCommunicator->getRecordedRequests();
        $responseData = null;
        $responseWallet = null;
        $responseWalletCertificates = null;

        foreach ($requests as $n => $xhr) {
            $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} ".json_encode($xhr->request->getHeaders()));

            if (stristr($xhr->request->getUri(), 'LoyaltyManagement/MileagePlanUI/api/Member?accountGuid=')) {
                $this->logger->notice("xhr response {$n} body: ".json_encode($xhr->response->getBody()));
                $responseData = json_encode($xhr->response->getBody());
            }
            if (stristr($xhr->request->getUri(), 'loyaltymanagement/wallet/wallet/balance')) {
                $this->logger->notice("xhr response {$n} body: ".json_encode($xhr->response->getBody()));
                $responseWallet = json_encode($xhr->response->getBody());
            }

            if (stristr($xhr->request->getUri(), 'loyaltymanagement/wallet/certificates')) {
                $this->logger->notice("xhr response {$n} body: ".json_encode($xhr->response->getBody()));
                $responseWalletCertificates = json_encode($xhr->response->getBody());
            }

            if (stristr($xhr->request->getUri(), 'customermobile/trips/list')) {
                $this->logger->notice("xhr response {$n} body: ".json_encode($xhr->response->getBody()));
                $this->trips = json_encode($xhr->response->getBody());
            }
        }

        $response = $this->http->JsonLog($responseData);

        $accounts_with_403 = [
            '265374502', // I don't know WTF with this chinese account, AccountID: 4370823
            '14811333',
            '156735762',
            'GregTWilliams',
            'felixlo@gmail.com',
            '207199930',
            '172271912',
            'porritt@wi.rr.com',
            'alankaionlam@college.harvard.edu',
            '143381534',
            '258134214',
        ];

        // it helps
        if (
            $this->http->Response['code'] == 403
            && !in_array($this->AccountFields['Login'], $accounts_with_403)
            && $this->AccountFields['Partner'] == 'awardwallet'
            && $this->attempt == 0
        ) {
            throw new CheckRetryNeededException(2, 0);
        }

        // provider bug
        if (
            isset($response->message, $response->statusCode)
            && $response->statusCode == 500
            && $response->message == 'Internal server error'
        ) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        $this->http->RetryCount = 2;

        // refs #10315, not a member
        if (
            $this->http->Response['code'] !== 403
            && !empty($response)
            && $response->loyalty === null
        ) {
            $this->SetWarning(self::NOT_MEMBER_MSG);
            // Name
            $firstName = $response->account->legalName->firstName ?? null;
            $lastName = $response->account->legalName->lastName ?? null;
            $this->SetProperty("Name", beautifulName("$firstName $lastName"));
            // Discount Codes   // refs #5308
            $alaskaair->discountCodes();
            // My wallet // refs #16282
            $this->myWallet($responseWallet, $responseWalletCertificates);
            // Guest upgrades // refs #16446
            $this->guestUpgrades();

            return;
        }

        if (!empty($response)) {
            // Balance - Available Miles
            $this->SetBalance($response->loyalty->memberBalance);
            // Status
            $status = $response->loyalty->tierName;

            if ($status == 'Regular') {
                $status = "Member";
            }

            $this->SetProperty("Status", $status);
            // Name
            $this->SetProperty("Name", beautifulName($response->loyalty->firstName . " " . $response->loyalty->lastName));
            // Mileage Plan number
            $this->SetProperty("Number", $response->loyalty->mileagePlanNumber);
            // Member Since
            if (!empty($response->loyalty->startDate)) {
                $this->SetProperty("MemberSince", date("m/d/Y", strtotime($response->loyalty->startDate)));
            }
            // YTD Alaska Miles
            $this->SetProperty("Miles", $response->loyalty->asMiles);
            // YTD Alaska Segments
            $this->SetProperty("Segments", $response->loyalty->asSegments);
            // YTD Qualifying Partner Miles
            $this->SetProperty("PartnerMiles", $response->loyalty->asoaMiles);
            // YTD Qualifying Partner Segments
            $this->SetProperty("PartnerSegments", $response->loyalty->asoaSegments);
            // Alaska Miles toward Million Mile Flyer
            $this->SetProperty("MillionMilerMiles", $response->loyalty->lifetimeMiles);
        }

        // Beginning of period for elite levels
        $this->SetProperty("YearBegins", strtotime("1 JAN"));

        // broken accounts
        if (
            $this->ErrorCode === ACCOUNT_ENGINE_ERROR
            && (
                // AccountID: 3619907, 216700
                $this->http->FindPreg('/^Unable to retrieve Mileage Plan information\.$/', false, $this->http->Response['body'])
                // I don't know WTF with this chinese account, AccountID: 4370823
                || in_array($this->AccountFields['Login'], $accounts_with_403)
                || $this->http->Response['code'] == 403
                // AccountID: 4868060, 4687752, 3516474
                || (
                    isset($response->loyalty)
                    && $response->loyalty->memberBalance === null
                    && $response->loyalty->asMiles === null
                    && $response->loyalty->statusMessage === "Active Member not found"
                    && $response->loyalty->tierName === null
                )
            )
        ) {
            $this->logger->notice("broken account, get properties from cookies");
            $fName = urldecode($this->http->getCookieByName("AS%5FNAME"));
            // Balance - Available miles
            $balance = $this->http->FindPreg("/BM=([^\&]+)/ims", false, $fName);
            $this->SetBalance(str_replace(",", "", $balance));

            if (
                $this->ErrorCode === ACCOUNT_ENGINE_ERROR
                && $this->http->FindPreg("/BM=&/ims", false, $fName)
            ) {
                $this->logger->notice("broken account, set Balance 0");
                $this->SetBalance(0);
            }

            // Mileage Plan #
            $this->SetProperty("Number", $this->http->FindPreg("/MP=([^\&]+)/ims", false, $fName));
            // Name
            $this->SetProperty("Name", beautifulName($this->http->FindPreg("/FN=([^\&]+)/", false, $fName) . " " . $this->http->FindPreg("/LN=([^\&]+)/", false, $fName)));
        }

        // Get property 'LastActivity' and Expiration Date // refs #7542, 4157, 21848
        $this->getExpirationDate();

        // Discount Codes   // refs #5308
        $this->discountCodes();
        // My wallet // refs #16282
        $this->myWallet($responseWallet, $responseWalletCertificates);
        // Guest upgrades // refs #16446
        $this->guestUpgrades();
        // Alaska Lounge Passes // refs #17434
        $alaskaair->alaskaLoungePasses();
    }

    private function getHistory()
    {
        $this->logger->notice(__METHOD__);

        if (!empty($this->history)) {
            return $this->history;
        }

        $this->http->GetURL("https://www.alaskaair.com/account/mileageplan/activity");
        $this->waitForElement(WebDriverBy::xpath('//div[contains(@class, "mp-memberActivity-card")]//tr[td]'), 7);
        $this->saveResponse();

        $seleniumDriver = $this->http->driver;
        $requests = $seleniumDriver->browserCommunicator->getRecordedRequests();
        $responseData = null;

        foreach ($requests as $n => $xhr) {
            $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} ".json_encode($xhr->request->getHeaders()));

            if (stristr($xhr->request->getUri(), 'mpactivitybff/member/activities')) {
                $this->logger->notice("xhr response {$n} body: ".json_encode($xhr->response->getBody()));
                $responseData = json_encode($xhr->response->getBody());
            }
        }

        $response = $this->http->JsonLog($responseData, 1);
        $this->logger->debug("Total " . ((is_array($response) || ($response instanceof Countable)) ? count($response) : 0) . " history rows were found");
        $this->history = $response;

        return $this->history;
    }

    private function getExpirationDate()
    {
        $this->logger->info('Expiration date', ['Header' => 3]);
        $response = $this->getHistory();

        if (!$response) {
            return;
        }

        $activities = $response->activities ?? [];

        foreach ($activities as $row) {
            // Activity Date
            $date = $row->activityDate;
            // Total
            $totalMiles = $row->total;
            $this->logger->debug("Date: {$date} / Miles: {$totalMiles}");

            if ($totalMiles != 0) {
                $lastActivityDate = strtotime($date);

                if ($lastActivityDate !== false) {
                    $this->SetProperty("LastActivity", $date);
                    $accountExpirationDate = strtotime("+3 year", $lastActivityDate);
                    $this->SetExpirationDate($accountExpirationDate);
                }// if ($lastActivityDate !== false)

                break;
            }// if ($totalMiles != 0)
        }// foreach ($response as $row)
    }

    private function discountCodes()
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info('Discount and companion fare codes', ['Header' => 3]);
        $this->http->GetURL("https://www.alaskaair.com/www2/ssl/myalaskaair/myalaskaair.aspx?view=discounts&lid=utilNav:discountCodes");

        sleep(5);

        $this->waitForElement(WebDriverBy::xpath('//*[@id = "validCodes"]//div[contains(., "There are no valid Discount Codes saved in your account")] | //div[contains(@class, "available-columns")]/table//tr[td[3]]'), 5);
        $this->saveResponse();

        if ($message = $this->http->FindPreg("/(There are no valid Discount Codes saved in your account\.)/ims")) {
            $this->logger->notice(">>>> " . $message);
        }

        $codes = $this->http->XPath->query('//div[contains(@class, "available-columns")]/table//tr[td[3]]');
        $this->logger->debug("Total nodes found: " . $codes->length);

        if ($codes->length > 0) {
            for ($i = 0; $i < $codes->length; $i++) {
                $code = $this->http->FindSingleNode('.//span[contains(text(), "Code:")]', $codes->item($i), true, "/Code:\s*(.+)/");
                $displayName = $this->http->FindSingleNode('.//td[2]', $codes->item($i), true, "/(.+)Code:/");
                $exp = $this->http->FindSingleNode('td[1]', $codes->item($i));
                $exp = str_replace('-', '/', $exp);
                $this->logger->debug(">>>> " . $exp);

                if (strtotime($exp)) {
                    $subAccounts[] = [
                        'Code'           => 'AlaskaairDiscountCodes' . $i,
                        'DisplayName'    => $displayName,
                        'Balance'        => null,
//                        'Discount'       => $this->http->FindSingleNode('td[2]', $codes->item($i)),
                        'DiscountCode'   => $code,
                        'ExpirationDate' => strtotime($exp),
                    ];
                }
            }// for ($i = 0; $i < $nodes->length; $i++)

            if (isset($subAccounts)) {
                //# Set Sub Accounts
                $this->SetProperty("CombineSubAccounts", false);
                $this->http->Log("Total subAccounts: " . count($subAccounts));
                //# Set SubAccounts Properties
                $this->SetProperty("SubAccounts", $subAccounts);
            }// if(isset($subAccounts))
        }// if ($codes->length > 0)
    }

    private function guestUpgrades()
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info('Guest upgrades', ['Header' => 3]);
        $this->http->GetURL("https://www.alaskaair.com/account/overview/guest-upgrades");

        sleep(5);

        $this->waitForElement(WebDriverBy::xpath('//*[@id = "validCodes"]//div[contains(., "No upgrade certificates to display")]'), 5);
        $this->saveResponse();

        $seleniumDriver = $this->http->driver;
        $requests = $seleniumDriver->browserCommunicator->getRecordedRequests();
        $responseData = null;

        foreach ($requests as $n => $xhr) {
            $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} ".json_encode($xhr->request->getHeaders()));

            if (stristr($xhr->request->getUri(), 'LoyaltyManagement/MileagePlanUI/api/MPVoucher')) {
                $this->logger->notice("xhr response {$n} body: ".json_encode($xhr->response->getBody()));
                $responseData = json_encode($xhr->response->getBody());
            }
        }

        $response = $this->http->JsonLog($responseData, 3, false, 'validVouchers');

        if (!isset($response->voucherDetailsByStatus->validVouchers)) {
            return;
        }

        $upgrades = $response->voucherDetailsByStatus->validVouchers;
        $this->logger->debug("Total " . count($upgrades) . " Guest upgrades were found");
        $count = (count($upgrades) > 8 ? 8 : count($upgrades));
        $this->logger->debug("Count: $count");

        for ($i = 0; $i < $count; $i++) {
            // Upgrade code
            $displayName = $upgrades[$i]->voucherNumber;
            // Expiration Date
            $exp = strtotime($upgrades[$i]->voucherExpiryDate, false);
            $subAcc = [
                'Code'           => 'alaskaairGuestUpgrades' . str_replace('-', '', $displayName),
                'DisplayName'    => "Upgrade code {$displayName}",
                'Balance'        => null,
                "ExpirationDate" => $exp,
            ];
            $this->AddSubAccount($subAcc, true);
        }
    }

    function myWallet($responseWallet, $responseWalletCertificates)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info('My wallet', ['Header' => 3]);

        if (empty($responseWallet)) {
            return;
        }

        $myWalletBalance = $this->http->FindPreg("/^([\d\.\-\,]+)$/", false, $responseWallet);

        if ($myWalletBalance === null) {
            $this->logger->error("something went wrong");

            return;
        }

        if (!empty($myWalletBalance) && $responseWalletCertificates) {
            $certificates = $this->http->JsonLog($responseWalletCertificates);
            $this->logger->debug("Total " . count($certificates) . " Certificates were found");
            $expirationList = [];

            foreach ($certificates as $certificate) {
                // Available Balance
                $balance = PriceHelper::cost($certificate->availableBalance);
                // Certificate Type
                $displayName = $certificate->type;

                if ($balance == '0.00' || $certificate->expirationDate === null) {
                    $this->logger->debug("Skip {$displayName} / {$balance}: {$certificate->expirationDate}");

                    continue;
                }
//                $this->logger->notice("Adding {$displayName} / {$balance}");
                // Expiration Date
                $exp = strtotime($certificate->expirationDate, false);

                if (isset($expirationList[$exp])) {
                    $expirationList[$exp]['ExpiringBalance'] = $expirationList[$exp]['ExpiringBalance'] + $balance;
                } else {
                    $expirationList[$exp]['ExpiringBalance'] = $balance;
                }
//                $this->logger->debug(var_export($expirationList, true), ['pre' => true]);
            }// foreach ($certificates as $certificate)
            ksort($expirationList);
        }// if (!empty($myWalletBalance))

        $subAcc = [
            'Code'        => 'alaskaairMyWallet',
            'DisplayName' => 'My Wallet',
            'Balance'     => $myWalletBalance,
        ];

        if (!empty($expirationList)) {
            // Expiration Date
            $subAcc["ExpirationDate"] = key($expirationList);
            $subAcc["ExpiringBalance"] = '$' . current($expirationList)['ExpiringBalance'];
        }// if (!empty($expirationList)

        $this->AddSubAccount($subAcc, true);
    }

    public function ParseItineraries()
    {
        $this->logger->notice(__METHOD__);
        $alaskaair = $this->getAlaskaair();
        $res = $this->http->JsonLog($this->trips);

        if ($res === null) {
            $this->logger->error("can't get list");

            return [];
        }

        if ($this->http->FindPreg("/^\{\"errors\":\[\],\"upcoming\":null,/", false, $this->trips)
            || $this->http->FindPreg("/^\{\"errors\":\[\],\"upcoming\":\[\],/", false, $this->trips)
            || $this->http->FindPreg("/^\{\"errors\":\[\{\"message\":\"No FFN found. Unable to call Loyalty Management GraphQL service.\",\"code\":\"FFNLookupFailed\"\}\],\"upcoming\":null/", false, $this->trips)
        ) {
            return $this->noItinerariesArr();
        }

        if (isset($res->upcoming) && is_array($res->upcoming)) {
            foreach ($res->upcoming as $upcoming) {
                $pnr = $upcoming->confirmationCode;
                $lname = $upcoming->passengers[0]->lastName;
                $this->increaseTimeLimit();
                $itinUrl = "https://www.alaskaair.com/booking/reservation-lookup?LNAME={$lname}&RECLOC={$pnr}&lid=myas:trips-upcoming-details";
                $alaskaair->http->GetURL($itinUrl);

                if ($this->http->FindPreg('/Warmup in progress. Please try again later./')
                    || $this->http->FindSingleNode("//h1[contains(text(),'Reservation temporarily inaccessible')]")
                    /*|| !$this->http->FindSingleNode("//span[contains(text(),'Confirmation ')]")*/) {
                    sleep(random_int(1, 7));
                    $alaskaair->http->GetURL($itinUrl);
                }

                $this->waitForElement(WebDriverBy::xpath('//div[@class="passenger-card"]'), 25);
                $this->saveResponse();

                $this->sendNotification('load it // MI');
                if ($this->http->FindPreg("/<h1>Error Encountered<\/h1>\s+<span id=\"_message\"><p>Our web server encountered an internal error/")
                    && $this->http->FindPreg('/Please\s+try\s+your\s+transaction\s+again/')
                ) {
                    // sometimes retry helps
                    sleep(random_int(1, 7));
                    $alaskaair->http->GetURL($itinUrl);

                    if ($this->http->FindPreg("/<h1>Error Encountered<\/h1>\s+<span id=\"_message\"><p>Our web server encountered an internal error/")
                        && $this->http->FindPreg('/Please\s+try\s+your\s+transaction\s+again/')
                    ) {
                        $this->logger->error("Error Encountered: Our web server encountered an internal error...");

                        continue;
                    }
                }

                if ($error = $this->http->FindSingleNode("//div[@class='errorTextSummary']")) {
                    $this->logger->error($error);

                    continue;
                }

                if ($error = $this->http->FindSingleNode("//*[self::div or self::p][contains(@class,'errorAdvisory')]")) {
                    $this->logger->error($error);

                    continue;
                }

                if ($this->http->FindSingleNode("//h1[contains(text(), 'Confirm Your Schedule Change')]")
                    && $this->http->FindSingleNode("//h2[contains(text(), 'New Schedule')]")
                    && !$this->http->FindSingleNode('//div[@class="passenger-card"]')
                ) {
                    $alaskaair->parseChangedItineraryV2(strtotime($upcoming->startDate), $upcoming->passengers);
                } elseif ($this->http->ParseForm('modern-view-pnr') || $this->http->FindNodes('//div[@class="passenger-card"]')) {
                    $this->logger->notice('ParseForm modern-view-pnr');

                    if (stristr($alaskaair->http->currentUrl(), '?source=modern-vpnr')) {
                        $this->logger->error('Something went wrong');
                        continue;
                    }
                    // We can't display your reservation right now, but we're here to help. Text with an agent now: 82008.
                    if ($error = $this->http->FindSingleNode('//div//div[contains(., "t display your reservation right now, but we")]')) {
                        $this->logger->error($error);
                        continue;
                    }

                    $alaskaair->parseItineraryHtmlV2($upcoming);
                } else {
                    if ($error = $this->http->FindSingleNode('//div[contains(@class, "contact-us-banner-container") and contains(., "ve changed your flight due to schedule updates. Your new flight details will be available shortly.")]')) {
                        $this->logger->error("[Notice]: {$error}");
                        continue;
                    }

                    $alaskaair->parseItineraryHtml();
                }
            }
        }

        return [];
    }

    public function GetHistoryColumns()
    {
        return [
            "Activity Date" => "PostingDate",
            "Activity Type" => "Description",
            "Status"        => "Info",
            "Miles"         => "Info",
            "Bonus"         => "Bonus",
            "Total"         => "Miles",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        $result = [];
        $this->logger->debug('[History start date: ' . ((isset($startDate)) ? date('Y/m/d H:i:s', $startDate) : 'all') . ']');

        $startIndex = sizeof($result);
        $pageResult = $this->ParsePageHistory($startIndex, $startDate);
        $result = array_merge($result, $pageResult);

        // Sort
        usort($result, function ($a, $b) {
            if ($a['Activity Date'] == $b['Activity Date']) {
                return 0;
            }

            return ($a['Activity Date'] < $b['Activity Date']) ? 1 : -1;
        });

        return $result;
    }

    private function ParsePageHistory($startIndex, $startDate)
    {
        $this->logger->notice(__METHOD__);
        $result = [];
        $response = $this->getHistory();

        if (!$response) {
            return [];
        }

        $activities = $response->activities ?? [];

        foreach ($activities as $row) {
            // Activity Date
            $dateStr = $row->activityDate;
            $postDate = strtotime($dateStr);

            if (isset($startDate) && $postDate < $startDate) {
                $this->logger->notice("break at date {$dateStr} ($postDate)");

                break;
            }

            $result[$startIndex]['Activity Date'] = $postDate;

            $desc = [
                $row->partnerName ?? '',
                $row->solarMarketingFlight ?? '',
                $row->productName ?? '',
            ];

            $result[$startIndex]['Activity Type'] = Html::cleanXMLValue(implode(' ', $desc));
            $result[$startIndex]['Status'] = $row->status;
            $result[$startIndex]['Miles'] = $row->miles ?? $row->points;
            $result[$startIndex]['Bonus'] = $row->bonus;
            $result[$startIndex]['Total'] = $row->total;
            $startIndex++;
        }// foreach ($response as $row)

        return $result;
    }

    /** @return TAccountCheckerAlaskaair */
    private function getAlaskaair()
    {
        if (!isset($this->alaskaair)) {
            $this->alaskaair = new TAccountCheckerAlaskaair();
            $this->alaskaair->AccountFields = $this->AccountFields;
            $this->alaskaair->http = $this->http;

            $this->alaskaair->HistoryStartDate = $this->HistoryStartDate;
            $this->alaskaair->historyStartDates = $this->historyStartDates;
            $this->alaskaair->http->LogHeaders = $this->http->LogHeaders;
            $this->alaskaair->ParseIts = $this->ParseIts;
            $this->alaskaair->ParsePastIts = $this->ParsePastIts;
            $this->alaskaair->WantHistory = $this->WantHistory;
            $this->alaskaair->WantFiles = $this->WantFiles;
            $this->alaskaair->strictHistoryStartDate = $this->strictHistoryStartDate;

            $this->alaskaair->itinerariesMaster = $this->itinerariesMaster;
            $this->alaskaair->logger = $this->logger;
            $this->alaskaair->globalLogger = $this->globalLogger; // fixed notifications
            $this->alaskaair->onTimeLimitIncreased = $this->onTimeLimitIncreased;
        }

        $this->logger->debug("set headers");
        $defaultHeaders = $this->http->getDefaultHeaders();

        foreach ($defaultHeaders as $header => $value) {
            $this->alaskaair->http->setDefaultHeader($header, $value);
        }

        return $this->alaskaair;
    }
}
