<?php

use AwardWallet\Common\Parsing\Html;
use AwardWallet\Engine\ProxyList;
use AwardWallet\Engine\thaiair\QuestionAnalyzer;
use Facebook\WebDriver\Exception\UnknownErrorException;

class TAccountCheckerThaiair extends TAccountChecker
{
    use SeleniumCheckerHelper;
    use ProxyList;
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    private $headers = [
        "Accept"                        => "application/json, text/plain, */*",
        "Accept-Language"               => "en-th",
        "Accept-Encoding"               => "gzip, deflate, br, zstd",
        "Content-Type"                  => "application/json",
        "source"                        => "website",
        "hostName"                      => "https://osci.thaiairways.com",
    ];

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setHttp2(true);
        $this->http->SetProxy($this->proxyReCaptchaIt7());
    }

    public function IsLoggedIn()
    {
        unset($this->State['token']);

        return $this->loginSuccessful();
    }

    private function loginSuccessful(): bool
    {
        $this->logger->notice(__METHOD__);

        if (!isset($this->State['Authorization'])) {
            return false;
        }

        $this->http->RetryCount = 0;
        $this->http->GetURL("https://osci.thaiairways.com/profile/fetchProfile", $this->headers + ["Authorization" => $this->State['Authorization']]);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();

        if (isset($response->memberID)) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->FilterHTML = false;

        if (strstr($this->AccountFields['Pass'], '@') || strstr($this->AccountFields['Pass'], '-') || strstr($this->AccountFields['Pass'], '!')) {
            throw new CheckException("Please check your PIN. Only alphabets and numbers are accepted.", ACCOUNT_INVALID_PASSWORD);
        }

        $this->http->removeCookies();
        $this->http->GetURL('https://osci.thaiairways.com/en-th/content/about-royal-orchid-plus/');
//        $this->getCookiesFromSelenium();

//        if (!$this->http->FindSingleNode("//input[@name = 'memberId']/@name")) {
        if ($this->http->Response['code'] != 200) {
            return $this->checkErrors();
        }

        $data = [
            "memberId" => strtoupper($this->AccountFields['Login']),
            "password" => $this->AccountFields['Pass']
        ];
        $this->http->RetryCount = 0;
        $headers = [
            "Access-Control-Expose-Headers" => "accessToken",
        ];
        $this->http->PostURL('https://osci.thaiairways.com/profile/login', json_encode($data), $this->headers + $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        // Back-end server is at capacity
        $header503 = $this->http->Response['headers']['http/1.1 503 service unavailable'] ?? '';

        if ($this->http->FindPreg('/Back-end server is at capacity/', false, $header503)) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }
        // Due to a temporary error the request could not be serviced
        if ($message = $this->http->FindSingleNode("//h4[contains(text(), 'Due to a temporary error the request could not be serviced')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // System is unavailable please try again
        if ($message = $this->http->FindPreg("/(System is unavilable please try again)/ims")) {
            throw new CheckException("System is unavailable please try again.", ACCOUNT_PROVIDER_ERROR);
        }
        // Sorry, online service is unavailable due to system upgrade
        if ($message = $this->http->FindSingleNode("//center[contains(text(), 'Sorry, online service is unavailable')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }
        // HTTP Status 404 / HTTP Status 503
        if ($this->http->FindPreg("/(>HTTP Status (?:404|503) -)/ims")
            // Proxy Error
            || $this->http->FindPreg("/(<h1>Proxy Error)<\/h1>/ims")
            || $this->http->FindPreg("/(<h1>Service Temporarily Unavailable)<\/h1>/ims")
            || $this->http->FindSingleNode("//h2[contains(text(), 'The request could not be satisfied.')]")) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();
        $authorization = $this->http->Response['headers']['authorization'] ?? null;

        if ($authorization) {
            $this->State['Authorization'] = $authorization;

            if ($this->loginSuccessful()) {
                return true;
            }

            return false;
        }

        // Please check your Member ID and Pin.
        if ($this->http->FindPreg('/^\{"status":false\}$/')) {
            throw new CheckException('Please check your Member ID and Pin.', ACCOUNT_INVALID_PASSWORD);
        }

        $otpRef = $response->otpRefKey ?? null;
        $email = $response->email ?? null;
        $accesstoken = $this->http->Response['headers']['accesstoken'] ?? null;

        if ($otpRef && $email && $accesstoken) {
            $question = "We have sent a 4-digit OTP code to your registered email address {$email}";

            if (!QuestionAnalyzer::isOtcQuestion($question)) {
                $this->sendNotification("Need to check sq");
            }

            $this->State['otpRef'] = $otpRef;
            $this->State['accesstoken'] = $accesstoken;
            $this->AskQuestion($question, null, 'Question');

            return false;
        }

        if ($message = $response->message ?? null) {
            $this->checkCredentials($message);
        }

        return $this->checkErrors();
    }

    public function ProcessStep($step)
    {
        $data = json_encode([
            'otpRef' => $this->State['otpRef'],
            'otpKey' => $this->Answers[$this->Question],
        ]);
        $headers = [
            'accessToken' => $this->State['accesstoken'],
        ];
        unset($this->Answers[$this->Question]);

        $this->http->RetryCount = 0;
        $this->http->PostURL('https://osci.thaiairways.com/profile/otp/submit', $data, $this->headers + $headers);
        $this->http->RetryCount = 2;

        if ($this->http->FindSingleNode("//iframe[contains(@src, '/_Incapsula_Resource?')]/@src")) {
            $this->incapsula();
            $this->http->RetryCount = 0;
            $this->http->PostURL('https://osci.thaiairways.com/profile/otp/submit', $data, $this->headers + $headers);
            $this->http->RetryCount = 2;
        }

        $respomse = $this->http->JsonLog();
        $message = $respomse->message ?? null;

        if ($message == 'Incorrect OTP code, please try again.') {
            $this->AskQuestion($this->Question, $message, 'Question');

            return false;
        }

        unset($this->State['accesstoken']);
        $authorization = $this->http->Response['headers']['authorization'] ?? null;

        if ($authorization) {
            $this->State['Authorization'] = $authorization;

            return $this->loginSuccessful();
        }

        if ($message = $response->message ?? null) {
            $this->checkCredentials($message);
        }

        return false;
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, 0);
        // Balance - Current Mileage
        $this->SetBalance($response->remainingMiles ?? null);
        // Name
        $name = Html::cleanXMLValue(
            $response->firstName." ".$response->lastName
        );
        $this->SetProperty("Name", beautifulName($name));
        // Status
        $this->SetProperty("Status", $response->privilegeCard ?? null);
        // Member ID
        $this->SetProperty("AccountNumber", $response->memberID	 ?? null);

        $this->http->GetURL("https://osci.thaiairways.com/loyalty/miles", $this->headers + ["Authorization" => $this->State['Authorization']]);
        $response = $this->http->JsonLog();
        $milesExpiry = $response->milesExpiry ?? [];

        if (!$milesExpiry) {
            return;
        }
        // Expiration Date  // refs #6469
        $countNodes = count($milesExpiry);
        $this->logger->debug("Total {$countNodes} exp nodes were found");
        $quarter = 0;

        foreach ($milesExpiry as $node) {
            $date = $node->milesExpiryDate;
            $expMiles = $node->amount;
            $this->logger->debug("Date: {$date} / {$expMiles}");

            if ($expMiles == 0) {
                $quarter++;
            }

            if ($expMiles > 0 && (!isset($exp) || $exp < strtotime($date))) {
                $exp = strtotime($date);
                // Miles To Expire
                $this->SetProperty("MilesToExpire", $expMiles);
                $this->SetExpirationDate($exp);
            }// if ($expMiles > 0 && strtotime($exp))
        }// foreach ($nodes as $node)

        if (
            (!isset($exp) && $quarter == $countNodes && $countNodes > 0)
            || (isset($exp) && $exp < strtotime("31 Dec 2022"))
        ) {
            $this->ClearExpirationDate();

            // refs #21189
            if (time() < strtotime("31 Dec 2022")) {
                $this->logger->notice("extend exp date by provider rules");
                $this->SetExpirationDate(strtotime("31 Dec 2023"));
            }
        }
    }

    public function GetHistoryColumns()
    {
        return [
            'Date'                    => 'PostingDate',
            'Code'                    => 'Info',
            'Flight Number'           => 'Info',
            'Service Class'           => 'Info',
            'Transaction Description' => 'Description',
            'Earned Miles'            => 'Miles',
            'Qualifying Miles'        => 'Info',
        ];
    }

    public function ParseHistory($startDate = null)
    {
        $result = [];
        $this->logger->debug('[History start date: ' . ((isset($startDate)) ? date('Y/m/d H:i:s', $startDate) : 'all') . ']');
        // get more than 1 year transactions
        $this->http->PostURL("https://osci.thaiairways.com/loyalty/miles/info", '{"filterBy":"ByPeriod","month":"12"}', $this->headers + ["Authorization" => $this->State['Authorization']]);
        $startIndex = sizeof($result);
        $result = $this->ParseHistoryPage($startIndex, $startDate);

        return $result;
    }

    public function ParseHistoryPage($startIndex, $startDate)
    {
        $result = [];
        $response = $this->http->JsonLog(null, 3, true);
        $nodes = ArrayVal($response, 'activityDetails', []);
        $this->logger->debug("Found " . count($nodes) . " items");
        // if history has only one row
        if (isset($nodes['activityDate'], $nodes['description'])) {
            $nodes = [$nodes];
        }

        foreach ($nodes as $node) {
            $dateStr = ArrayVal($node, 'activityDate');
            $postDate = strtotime($dateStr);

            if (isset($startDate) && $postDate < $startDate) {
                $this->logger->debug("break at date {$dateStr} ($postDate)");

                break;
            }
            $result[$startIndex]['Date'] = $postDate;

            $milesDetails = ArrayVal($node, 'milesDetails', []);

            foreach ($milesDetails as $milesDetail) {
                switch ($milesDetail['labelName']) {
                    case 'Code':
                        $result[$startIndex]['Code'] = ArrayVal($milesDetail, 'labelValue');
                        break;
                    case 'Class':
                        $result[$startIndex]['Service Class'] = ArrayVal($milesDetail, 'labelValue');
                        break;
                    case 'Flight Number':
                        $result[$startIndex]['Flight Number'] = ArrayVal($milesDetail, 'labelValue');
                        break;
                    case 'Qualifying Miles':
                        $result[$startIndex]['Qualifying Miles'] = ArrayVal($milesDetail, 'labelValue');
                        break;
                    case 'Miles Earned':
                        $result[$startIndex]['Earned Miles'] = ArrayVal($milesDetail, 'labelValue');
                }
            }

            $descriptionDetails = ArrayVal($node, 'description');

            if (ArrayVal($descriptionDetails, 'description')) {
                $result[$startIndex]['Transaction Description'] = ArrayVal($descriptionDetails, 'description');
            } else {
                $description = [];

                if (is_array($descriptionDetails)) {
                    foreach ($descriptionDetails as $descriptionDetail) {
                        $description[] = ArrayVal($descriptionDetail, 'description');
                    }
                }
                $result[$startIndex]['Transaction Description'] = implode('; ', $description);
            }

            $startIndex++;
        }

        return $result;
    }

    public function ConfirmationNumberURL($arFields)
    {
        return 'https://www.thaiairways.com/en/Manage_My_Booking/My_Booking.page';
    }

    public function CheckConfirmationNumberInternal($arFields, &$it)
    {
        // Reservation retrieve form screenshot 1447243200
        // Reservation details screenshot 1447243229
        $this->http->SetProxy($this->proxyReCaptcha());
        $this->http->LogHeaders = true;

        $this->http->GetURL($this->ConfirmationNumberURL($arFields));

        $parseStatus = $this->http->ParseForm("viewbookingform");

        if (!$parseStatus) {
            return $this->notifications($arFields);
        }

        // $this->http->FormURL = 'http://booking.thaiairways.com/retrievePnrEnc/PnrController';
        $this->http->FormURL = 'https://www.thaiairways.com/retrievePnrEnc/PnrController';

        $inputs = [
            'pnrCode'                  => $arFields['ConfNo'],
            'pnr_Code'                 => 'Enter Reservation Code',
            'lastName'                 => $arFields['LastName'],
            'last_Name'                => 'Enter Passenger Last Name',
            'frdPath'                  => '/Manage_My_Booking/view_booking',
            'iwPreActions'             => 'redirectViewBooking',
            'LANGUAGE'                 => 'GB',
            'REC_LOC'                  => $arFields['ConfNo'],
            'DIRECT_RETRIEVE_LASTNAME' => $arFields['LastName'],
        ];
        // unset($this->http->Form['pnr_Code']);
        // unset($this->http->Form['last_Name']);
        // unset($this->http->Form['frdPath']);
        // unset($this->http->Form['iwPreActions']);

        foreach ($inputs as $key => $value) {
            $this->http->SetInputValue($key, $value);
        }

        $postStatus = $this->http->PostForm();

        if (!$postStatus) {
            return $this->notifications($arFields);
        }

        $this->http->FilterHTML = false;
        $parseStatus = $this->http->ParseForm('form1');
        $formURL = $this->http->FormURL;
        $form = $this->http->Form;

//        if (isset($responseStr->challenge, $responseStr->gt)) {
//            $captcha = $this->parseGeetTestCaptcha($responseStr->gt, $responseStr->challenge);

        if (!$parseStatus) {
            return $this->notifications($arFields);
        }

        $this->http->RetryCount = 0;
        $postStatus = $this->http->PostForm();

        if (!$postStatus) {
            return $this->notifications($arFields);
        }

        $this->http->RetryCount = 2;

        if ($message = $this->http->FindPreg('/We are unable to find this confirmation number. Please validate your entry and try again/i')) {
            return $message;
        }

        $it['Kind'] = "T";
        $it['RecordLocator'] = $this->http->FindPreg('/locator":"([^\"]+)"/');
        // ReservationDate
        $reservationDate = $this->http->FindPreg('/creationDate":"([^\"]+)"/');

        if ($reservationDate = strtotime($reservationDate)) {
            $it['ReservationDate'] = $reservationDate;
        }
        // Passengers
        $json = $this->http->FindPreg('/"TravellerList"\s*:\s*(\{.+\})\s*,\s*"UpgradeServiceBreakdown"/');
//        $this->http->Log("<pre>".var_export($json, true)."</pre>", false);
        $travellerList = $this->http->JsonLog($json, 3, true);
        $travellers = ArrayVal($travellerList, 'Travellers', []);
        $numbers = [];

        foreach ($travellers as $traveller) {
            $it['Passengers'][] = beautifulName(
                ArrayVal($traveller['IdentityInformation'], 'IDEN_TitleName') . " " .
                ArrayVal($traveller['IdentityInformation'], 'IDEN_FirstName') . " " .
                ArrayVal($traveller['IdentityInformation'], 'IDEN_LastName')
            );
            // AccountNumbers
            if (isset($traveller['FrequentFlyer'][0])) {
                $number = ArrayVal($traveller['FrequentFlyer'][0], 'FREQ_Airline') . " " . ArrayVal($traveller['FrequentFlyer'][0], 'FREQ_Number');
            }

            if (!empty($number)) {
                $numbers[] = $number;
            }
        }// foreach ($travellers as $traveller)
        // AccountNumbers
        if (!empty($numbers)) {
            $it['AccountNumbers'] = $numbers;
        }
        // may be wrong values
//        // TotalCharge
//        $it['TotalCharge'] = $this->http->FindPreg('/"totalAmount":\s*([\d\.]+),\s*"tax"/');
//        // Tax
//        $it['Tax'] = $this->http->FindPreg('/"tax":\s*([\d\.]+),/');
//        // BaseFare
//        $it['BaseFare'] = $this->http->FindPreg('/"amountWithoutTaxAndFee":\s*([\d\.]+),/');
//        // Currency
//        $it['Currency'] = $this->http->FindPreg('/"currency":\s*\{"name":\s*"[^\"]+"\s*,\s*"code":\s*"([A-Z]{3})"\s*,/');

        // Segments

        $json = $this->http->FindPreg('/"ListItineraryView"\s*:\s*(\{.+)\}\s*,\s*"forms"/ims');
//        $this->http->Log("<pre>".var_export($json, true)."</pre>", false);
        $listItineraryView = $this->http->JsonLog($json, 3, true);
        $listItineraryElem = ArrayVal($listItineraryView, 'listItineraryElem', []);
        $this->logger->debug("Total " . count($listItineraryElem) . " legs were found");

        foreach ($listItineraryElem as $elem) {
            $segments = ArrayVal($elem, 'listSegment', []);
            $this->logger->debug("Total " . count($segments) . " segments were found");

            foreach ($segments as $segment) {
                $seg = [];
//                $this->http->Log("segment: <pre>".var_export($segment, true)."</pre>", false);
                // FlightNumber
                $seg['FlightNumber'] = ArrayVal($segment, 'flightNumber');
                // DepName
                $seg['DepName'] = ArrayVal($segment['beginLocation'], 'locationName') . ", " . ArrayVal($segment['beginLocation'], 'cityName') . ", " . ArrayVal($segment['beginLocation'], 'countryName');
                // DepCode
                $seg['DepCode'] = ArrayVal($segment['beginLocation'], 'locationCode');
                // DepDate
                $seg['DepDate'] = strtotime(ArrayVal($segment, 'beginDate'));
                // DepartureTerminal
                $seg['DepartureTerminal'] = ArrayVal($segment, 'beginTerminal');
                // ArrName
                $seg['ArrName'] = ArrayVal($segment['endLocation'], 'locationName') . ", " . ArrayVal($segment['endLocation'], 'cityName') . ", " . ArrayVal($segment['endLocation'], 'countryName');
                // ArrivalTerminal
                $seg['ArrivalTerminal'] = ArrayVal($segment, 'endTerminal');
                // ArrCode
                $seg['ArrCode'] = ArrayVal($segment['endLocation'], 'locationCode');
                // ArrDate
                $seg['ArrDate'] = strtotime(ArrayVal($segment, 'endDate'));
                // AirlineName
                $seg['AirlineName'] = ArrayVal($segment['airline'], 'name');
                // Aircraft
                $seg['Aircraft'] = ArrayVal($segment['equipment'], 'name');
                // Cabin
                $seg['Cabin'] = ArrayVal($segment['listCabin'][0], 'name');

                $it['TripSegments'][] = $seg;
            }// foreach ($segments as $segment)
        }// foreach ($listItineraryElem as $elem)

        return null;
    }

    public function GetConfirmationFields()
    {
        return [
            "ConfNo" => [
                "Caption"  => "Confirmation #",
                "Type"     => "string",
                "Size"     => 20,
                "Required" => true,
            ],
            "LastName" => [
                "Caption"  => "Last Name",
                "Type"     => "string",
                "Size"     => 40,
                "Required" => true,
            ],
        ];
    }

    protected function incapsula()
    {
        $this->logger->notice(__METHOD__);
        $referer = $this->http->currentUrl();
        $incapsula = $this->http->FindSingleNode("//iframe[contains(@src, '/_Incapsula_Resource?')]/@src");

        if (isset($incapsula)) {
            sleep(2);
            $this->http->NormalizeURL($incapsula);
            $this->http->RetryCount = 0;
            $this->http->FilterHTML = false;
            $this->http->GetURL($incapsula);
            $this->http->RetryCount = 2;
            $this->logger->debug("parse captcha form");
            $action = $this->http->FindPreg("/xhr.open\(\"POST\", \"([^\"]+)/");

            if (!$action) {
                return false;
            }
            $captcha = $this->parseHCaptcha($referer);

            if ($captcha === false) {
                return false;
            }
            $this->http->RetryCount = 0;
            $this->http->PostURL('https://www.thaiairways.com' . $action, ['g-recaptcha-response' => $captcha], ["Referer" => $referer, "Content-Type" => "application/x-www-form-urlencoded"]);
            $this->http->RetryCount = 2;
            $this->http->FilterHTML = true;
            sleep(2);
            //$this->http->GetURL($referer);
        }// if (isset($distil))

        return true;
    }

    protected function parseHCaptcha()
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindSingleNode("//div[@class='h-captcha']/@data-sitekey");

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "method"    => "hcaptcha",
            "pageurl"   => $currentUrl ?? $this->http->currentUrl(),
            "proxy"     => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    protected function parseReCaptcha($referer)
    {
        $this->logger->notice(__METHOD__);
        $key = $this->http->FindSingleNode("(//div[@class = 'g-recaptcha']/@data-sitekey)[1]");

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer(self::CAPTCHA_RECOGNIZER_RUCAPTCHA);
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => $referer ? $referer : $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function checkCredentials($message)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->error("[Error]: '{$message}'");

        if (
            strstr($message, 'Your credential is incorrect.')
        ) {
            throw new CheckException('Your credentials are incorrect. Please try again or reset your password.', ACCOUNT_INVALID_PASSWORD);
        }

        if (strstr($message, 'Unable To send OTP.')) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        if ($message == "Too many failed sign in attempts. Please go to Royal Orchid Plus Self Unlock Account to unlock your account.") {
            throw new CheckException($message, ACCOUNT_LOCKOUT);
        }

        $this->DebugInfo = $message;
    }

    private function notifications($arFields)
    {
        $this->sendNotification("failed to retrieve itinerary by conf #", 'all', true,
            "Conf #: <a target='_blank' href='https://awardwallet.com/manager/loyalty/logs?ConfNo={$arFields['ConfNo']}'>{$arFields['ConfNo']}</a><br/>Name: {$arFields['LastName']}");

        return null;
    }

    private function getCookiesFromSelenium()
    {
        $this->logger->notice(__METHOD__);
        $selenium = clone $this;
        $retry = false;
        $this->http->brotherBrowser($selenium->http);

        try {
            $selenium->UseSelenium();
            $selenium->useGoogleChrome();
            $selenium->http->saveScreenshots = true;
            $selenium->http->start();
            $selenium->Start();
            $this->http->userAgent = $selenium->seleniumOptions->userAgent;
            $selenium->driver->manage()->window()->maximize();
            $selenium->http->GetURL('https://osci.thaiairways.com/en-th/content/about-royal-orchid-plus/');
            if ($signIn = $selenium->waitForElement(WebDriverBy::xpath('//span[contains(text(), "Sign In")]'), 10)) {
                $signIn->click();
                $selenium->waitForElement(WebDriverBy::xpath('//input[@name="memberId"]'), 10);
            }
            $this->savePageToLogs($selenium);

            foreach ($selenium->driver->manage()->getCookies() as $cookie) {
                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
            }
        } catch (UnknownErrorException $e) {
            $this->logger->error('Exception: ' . $e->getMessage(), ['pre' => true]);

            if (stripos($e->getMessage(), 'page crash') !== false) {
                $retry = true;
            }
        } finally {
            $selenium->http->cleanup();

            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                throw new CheckRetryNeededException(3, 5);
            }
        }
    }
}
