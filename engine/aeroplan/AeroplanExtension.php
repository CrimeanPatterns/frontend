<?php

namespace AwardWallet\Engine\aeroplan;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ConfNoOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithConfNoInterface;
use AwardWallet\ExtensionWorker\LoginWithConfNoResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\ParseHistoryInterface;
use AwardWallet\ExtensionWorker\ParseHistoryOptions;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesOptions;
use AwardWallet\ExtensionWorker\RetrieveByConfNoInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;
use DateTime;
use Doctrine\DBAL\Driver\Exception;

class AeroplanExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ParseHistoryInterface, ParseItinerariesInterface, RetrieveByConfNoInterface, LoginWithConfNoInterface
{
    use TextTrait;
    private $history;
    private $fromIsLoggedIn = false;
    private $lastName;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.aircanada.com/aeroplan/member/dashboard?lang=en-CA';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate($xpath = '
            //div[contains(@class, "ac-account-menu-user-name-points")]
            | //div[contains(@class, "welcome-msg")]
            | //ac-button[contains(@class, "ac-account-signin-button")]/button
            | //button[@id="libraUserMenu-signIn"]
            | //input[contains(@name, "username")]
        ', EvaluateOptions::new()->timeout(60)->allowNull(true));
        //$tab->logPageState();

        if (!$el) {
            return false;
        }

        if ($el->getNodeName() == 'BUTTON'
            && stristr($el->getAttribute('id'), 'UserMenu-signIn')) {
            sleep(3);
            $el = $tab->evaluate($xpath, EvaluateOptions::new()->timeout(0)->allowNull(true));
            //$tab->logPageState();
            if (!$el) {
                return false;
            }
        }

        if (
            (
                $el->getNodeName() == 'BUTTON'
                && stristr($el->getAttribute('id'), 'acUserMenu')
            ) || (
                $el->getNodeName() == 'DIV'
                && stristr($el->getAttribute('class'), 'welcome-msg')
            ) || (
                $el->getNodeName() == 'DIV'
                && stristr($el->getAttribute('class'), 'ac-account-menu-user-name-points')
            )                
        ) {
            $this->fromIsLoggedIn = true;

            return true;
        }

        return false;
    }

    public function getLoginId(Tab $tab): string
    {
        //url profile page
        $tab->gotoUrl("https://www.aircanada.com/ca/en/aco/home/app.html#/viewprofile");
        $loginIdElm = $tab->evaluate(
            '//text()[normalize-space()="Aeroplan number"]/following::text()[normalize-space()][1]',
            EvaluateOptions::new()
                ->nonEmptyString()
                ->allowNull(true)
                ->timeout(70)
        );

        //$tab->logPageState();

        if ($loginIdElm) {
            $loginId = $loginIdElm->getInnerText();

            return str_replace(' ', '', $loginId);
        } else {
            $this->logger->warning('LoginId not found');
        }

        return '';
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl("https://www.aircanada.com/clogin/pages/logout");
        $el = $tab->evaluate('
            //ac-button[contains(@class, "ac-account-signin-button")]/button
            | //button[@id="libraUserMenu-signIn"]
            | //a[@aria-label="Sign out"]
            | //abc-button[contains(@class, "libra-signin")]
        ', EvaluateOptions::new()->timeout(60)->allowNull(true));
        $this->logger->debug($tab->getUrl());
        $tab->logPageState();

        if (isset($el) && $el->getNodeName() == 'A') {
            $el->click();
            $el = $tab->evaluate('
                //ac-button[contains(@class, "ac-account-signin-button")]/button
                | //button[@id="libraUserMenu-signIn"]
                | //abc-button[contains(@class, "libra-signin")]
            ', EvaluateOptions::new()->timeout(60)->allowNull(true));
        }
        $tab->logPageState();

        if (
            !isset($el)
            && $this->findPreg("/(?:logout|redirect)/", $tab->getUrl())
        ) {
            $tab->gotoUrl($tab->getUrl());
            $el = $tab->evaluate('
                //ac-button[contains(@class, "ac-account-signin-button")]/button
                | //button[@id="libraUserMenu-signIn"]
                | //abc-button[contains(@class, "libra-signin")]
            ', EvaluateOptions::new()->timeout(60)->allowNull(true));
        }
        $tab->logPageState();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $signInButton = $tab->evaluate('
            //ac-button[contains(@class, "ac-account-signin-button")]/button
            | //button[@id="libraUserMenu-signIn"]
            | //input[contains(@name, "username")]
        ', EvaluateOptions::new()->allowNull(true)->timeout(60));
        $this->logger->debug($tab->getUrl());
        $tab->saveHtml();

        if (isset($signInButton)) {
            $signInButton->click();
        }

        $login = $tab->evaluate('//input[contains(@name, "username")]');
        $login->setValue($credentials->getLogin());
        $password = $tab->evaluate('//input[contains(@id, "password")]');
        $password->setValue($credentials->getPassword());
        sleep(1); // Otherwise, the button doesn't work.
        $tab->evaluate("//input[contains(@id, 'password')]/following::input[1]")->click();

        // | //div[contains(@class,"gigya-captcha-wrapper")]
        $submitResult = $tab->evaluate('
            //div[@id="FunCaptcha"]
            | //div[contains(@class, "welcome-msg")]
            | //div[contains(@class, "ac-account-menu-user-name-points")]
            | //input[contains(@class, "gigya-code-input")]
            | //div[contains(@class,"gigya-error-display")]/div[contains(@class,"gigya-error-msg-active")]
            | //div[contains(@class,"arkose-")]/iframe
        ');

        if (
            $submitResult->getNodeName() == 'BUTTON'
            || (
                $submitResult->getNodeName() == 'DIV'
                && stristr($submitResult->getAttribute('class'), 'welcome-msg')
            )
        ) {
            return new LoginResult(true);
        }

        // Captcha
        if (
            stristr($submitResult->getAttribute('src'), 'arkoselabs')
            || stristr($submitResult->getAttribute('id'), 'gig_captcha_')
            || stristr($submitResult->getInnerText(), 'To login, confirm you are not a robot')
            || stristr($submitResult->getAttribute('id'), 'FunCaptcha')
        ) {
            $tab->showMessage('In order to log in into this account, you need to solve the CAPTCHA below and click the "Sign in" button.');
            /*| //span[@id="gigya-error-msg-gigya-login-form-loginID"]
            | //span[@id="gigya-error-msg-gigya-login-form-password"]
            | //div[contains(@class,"gigya-error-msg-active") and not(contains(text(), "To login, confirm you are not a robot"))]*/
            sleep(5); // for debug purposes
            $submitResult = $tab->evaluate('
                //div[contains(@class, "welcome-msg")]
                | //div[contains(@class, "ac-account-menu-user-name-points")]
                | //input[contains(@class, "gigya-code-input")]
            ', EvaluateOptions::new()->allowNull(true)->timeout(120));

            if (!isset($submitResult)) {
                return LoginResult::captchaNotSolved();
            }
        }

        if (
            stristr($submitResult->getAttribute('class'), 'gigya-error-msg-active')
        ) {
            $error = $submitResult->getInnerText();
            $this->logger->debug('error string length: ' . strlen($error));
            $this->logger->debug('error string is empty: ' . (int) (strlen(trim($error) == 0)));

            if (
                strstr($error, "We're not able to validate the Aeroplan number or email address and password provided")
            ) {
                return LoginResult::invalidPassword($error);
            }

            if (strlen(trim($error) == 0)) {
                $this->logger->debug('empty error string');

                return LoginResult::providerError('The website is experiencing technical difficulties, please try to check your balance at a later time.');
            }

            return new LoginResult(false, $error);
        }

        if ($submitResult->getNodeName() == 'INPUT') {
            $tab->showMessage(message::identifyComputer('Submit'));
            $submitResult = $tab->evaluate('
                //div[contains(@class, "welcome-msg")]
                | //div[contains(@class, "ac-account-menu-user-name-points")]
                | //span[@id="gigya-error-msg-gigya-login-form-loginID"]
                | //span[@id="gigya-error-msg-gigya-login-form-password"]                
            ', EvaluateOptions::new()->allowNull(true)->timeout(180));

            if (!isset($submitResult)) {
                return LoginResult::identifyComputer();
            }

            return LoginResult::success();
        }

        if (
            $submitResult->getNodeName() == 'BUTTON'
            || (
                $submitResult->getNodeName() == 'DIV'
                && stristr($submitResult->getAttribute('class'), 'welcome-msg')
            )
        ) {
            return new LoginResult(true);
        }

        if (
            $submitResult->getNodeName() == 'SPAN'
            && strstr($submitResult->getAttribute('class'), 'gigya-error-msg-gigya-login-form-')
        ) {
            return LoginResult::invalidPassword($submitResult->getInnerText());
        }

        $tab->saveHtml();
        $this->logger->debug('Unknown error. Node name: ' . $submitResult->getNodeName() . ', inner text: ' . $submitResult->getInnerText());
        $this->logger->debug('Node class: ' . $submitResult->getAttribute('class'));
        $this->logger->debug('Node id: ' . $submitResult->getAttribute('id'));

        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        if (!$this->getToken($tab)) {
            $this->parseWitoutToken($tab, $master, $accountOptions);

            return;
        }

        $headers = $this->getHeaders($tab);

        try {
            $options = [
                'method'      => 'post',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
                'body'        => [],
            ];
            $json = $tab->fetch('https://akamai-gw.dbaas.aircanada.com/loyalty/profile/getProfileKilo?profiletype=complete', $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $response = json_decode($json);

        if (empty($response->accountHolder)) {
            if ($this->fromIsLoggedIn == true && isset($response->message) && $response->message == 'Unauthorized') {
                throw new \CheckRetryNeededException(2, 1);
            }

            return;
        }

        $accountHolder = $response->accountHolder;
        $statement = $master->createStatement();

        if (isset($accountHolder->name->lastName)) {
            $this->lastName = $accountHolder->name->lastName;
        }

        if (isset($accountHolder->name->firstName, $accountHolder->name->lastName)) {
            // Name
            $statement->addProperty("Name", beautifulName($accountHolder->name->firstName . " " . $accountHolder->name->lastName));
        }

        if (isset($accountHolder->loyalty->fqtvNumber)) {
            // Aeroplan Number
            $statement->addProperty("AccountNumber", $accountHolder->loyalty->fqtvNumber);
        }
        // Balance - Aeroplan Miles
        // refs #22366, 22458
        //        $this->SetBalance($accountHolder->aeroplanProfile->points->totalPoints ?? null);
        if (isset($accountHolder->aeroplanProfile->points->totalPoolPoints)) {
            // Family balance
            $statement->addProperty("FamilyBalance", $accountHolder->aeroplanProfile->points->totalPoolPoints);
        }

        if (isset($accountHolder->aeroplanProfile->statusCode)) {
            // Status
            $statement->addProperty("Status", $accountHolder->aeroplanProfile->statusCode);
        }

        if (isset($accountHolder->aeroplanProfile->acTierExpiry)) {
            // Status is valid Until
            $statement->addProperty("StatusIsValidUntil", str_replace('-', ' ', $accountHolder->aeroplanProfile->acTierExpiry ?? null));
        }

        // refs #22458
        $this->logger->info('Balance', ['Header' => 3]);
        $token = $this->getToken($tab);
        $authorization = "Bearer {$token}";

        try {
            $options = [
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'method'      => 'get',
                'headers'     => ['Authorization' => $authorization],
            ];
            $json = $tab->fetch('https://akamai-gw.dbaas.aircanada.com/loyalty/pooling/poolDashboard', $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $response = json_decode($json);

        if (isset($response->hohffp) && $statement->getProperties()['AccountNumber'] == $response->hohffp) {
            $statement->SetBalance($response->hohPoints);
        } elseif (isset($response->memberDetails)) {
            foreach ($response->memberDetails as $memberDetail) {
                if ($statement->getProperties()['AccountNumber'] !== $memberDetail->memberffp) {
                    continue;
                }

                $statement->SetBalance($memberDetail->individualPoints);

                break;
            }
        } // if (isset($response->memberDetails))
        else {
            $statement->SetBalance($accountHolder->aeroplanProfile->points->totalPoints);
        }

        // Account expiration date
        $this->logger->info('Expiration date', ['Header' => 3]);
        $this->getHistoryData($tab, $headers);

        foreach ($this->history as $activityDetail) {
            $dateStr = $activityDetail->date;
            $lastActivity = strtotime($dateStr);
            if (!$lastActivity) {
                break;
            }

            /*
            $statement->addProperty("LastActivity", date("M d, Y", $lastActivity));
            */
            $statement->addProperty("LastActivity", $lastActivity);

            break;
        } // foreach ($this->history as $activityDetail)

        // refs #21119
        if (
            isset($accountHolder->aeroplanProfile->statusCode, $lastActivity)
            && $accountHolder->aeroplanProfile->statusCode == 'BASE'
        ) {
            $exp = strtotime("+18 months", $lastActivity);
            $warning = "The balance on this award program due to expire on " . date("m/d/Y", $exp) . "
<br />
<br />
Air Canada (Aeroplan) states the following on their website: <a href=\"https://www.aircanada.com/ca/en/aco/home/aeroplan/your-aeroplan/inactivity-policy.html#/\" target=\"_blank\">&quot;You have 18 months before your Aeroplan points expire if there has been no activity in your account - meaning you haven’t earned, redeemed, donated, transferred or converted any points. But so long as you stay active, your points won’t expire at all&quot;</a>.
<br />
<br />
We determined that the last time you had account activity with Aeroplan was on " . date("m/d/Y", $lastActivity) . ", so the expiration date was calculated by adding 18 months to this date.";

            // 30 Nov 2025, refs #21119
            if ($exp < 1764460800) {
                $exp = 1764460800;
                $warning = "<a href='https://www.aircanada.com/ca/en/aco/home/aeroplan/news/points-expiry-suspended.html#/'>Air Canada (Aeroplan) states on its website that all point expirations are suspended through November 30, 2025.</a>";
            }

            $statement->SetExpirationDate($exp);
            $statement->addProperty("AccountExpirationWarning", $warning);
        } elseif (
            isset($accountHolder->aeroplanProfile->statusCode, $lastActivity)
            && $accountHolder->aeroplanProfile->statusCode != 'BASE'
        ) {
            $statement->setNeverExpires(true);
            $statement->addProperty("ClearExpirationDate", "Y");
            $statement->addProperty("AccountExpirationWarning", "do not expire with elite status");
        }

        try {
            $options = [
                'method'      => 'get',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers + ['version' => 'V6'],
            ];
            $json = $tab->fetch('https://akamai-gw.dbaas.aircanada.com/loyalty/currency', $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $response = json_decode($json);

        if (isset($response->currencyThresholds->currentValues->SQD)) {
            // Status Qualifying Dollars
            $statement->addProperty("QualifyingDollars", $response->currencyThresholds->currentValues->SQD ?? null);
        }

        if (isset($response->currencyThresholds->currentValues->SQS)) {
            // Status Qualifying Segments
            $statement->addProperty("QualifyingSegments", $response->currencyThresholds->currentValues->SQS ?? null);
        }

        if (isset($response->currencyThresholds->currentValues->SQM)) {
            // Status Qualifying Miles
            $statement->addProperty("QualifyingMiles", $response->currencyThresholds->currentValues->SQM ?? null);
        }

        try {
            $options = [
                'method'      => 'get',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
            ];
            $json = $tab->fetch('https://akamai-gw.dbaas.aircanada.com/loyalty/webviewprofile', $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $response = json_decode($json);
        $enrolmentDate = $response->loyaltyProfile->memberAccount->enrolmentDate ?? null;

        if (isset($enrolmentDate) && strtotime($enrolmentDate)) {
            // Member since
        }
        $statement->addProperty('EnrollmentDate', strtotime($enrolmentDate));

        // refs #21121
        $this->logger->info('eUpgrade credits / Flight Reward Certificate', ['Header' => 3]);

        try {
            $options = [
                'method'      => 'get',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
            ];
            $json = $tab->fetch('https://akamai-gw.dbaas.aircanada.com/loyalty/benefits?generateMllpPasses=true', $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $response = json_decode($json);
        $pointDetails = $response->pointDetails ?? [];

        foreach ($pointDetails as $pointDetail) {
            if (!in_array(
                $pointDetail->pointType,
                [
                    'EUPGSTANDARD', // eUpgrade credits
                    'FRC', // Flight Reward Certificate
                ]
            )) {
                continue;
            }

            $expiryDetails = $pointDetail->expiryDetails ?? [];
            unset($exp);

            $expData = [];

            foreach ($expiryDetails as $expiryDetail) {
                if (
                    (!isset($exp) || $exp > strtotime($expiryDetail->expiryDate))
                    && isset($expiryDetail->points)
                    && $expiryDetail->points > 0
                ) {
                    $exp = $expiryDetail->expiryDate;
                    $expData = [
                        'ExpiringBalance' => $expiryDetail->points,
                        'ExpirationDate'  => strtotime($exp),
                    ];
                }
            } // foreach ($expiryDetails as $expiryDetail)

            $displayName = 'eUpgrade credits';
            $code = 'UpgradeCredits';

            if ($pointDetail->pointType == 'FRC') {
                $displayName = 'Flight Reward Certificate';
                $code = 'FlightRewardCertificate';
            }

            $statement->AddSubAccount([
                'Code'        => $code,
                'DisplayName' => $displayName,
                'Balance'     => $pointDetail->points,
            ] + $expData);
        } // foreach ($pointDetails as $pointDetail)
    }

    public function getHistoryData(Tab $tab, array $headers)
    {
        $this->logger->notice(__METHOD__);
        $fromDate = date('Y-m-d', strtotime('-1 years'));
        $toDate = date('Y-m-d');

        try {
            $options = [
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'method'      => 'post',
                'headers'     => $headers,
                'body' => '{"query":"query getDetails($fromDate: String!, $toDate: String!, $limit: Int!, $language: String!, $sortOrder: String!, $pageNumber: Int!, $index: Int!, $pointType: [String]!) {\n  transactionHistory(\n    transactionHistoryInput: {fromDate: $fromDate, toDate: $toDate, limit: $limit, language: $language, sortOrder: $sortOrder, pageNumber: $pageNumber, index: $index, pointType: $pointType}\n  ) {\n    source\n    success\n    activityDetails {\n      refNumber\n      date\n      code\n      type\n      friendlyDescription\n      secondaryDescription\n      redeemablePoint {\n        code\n        name\n        quantity\n        pointsIndicator\n        contentColour\n        contentColourDark\n        bonusIncluded\n      }\n      partnerInfo {\n        code\n        friendlyName\n        category\n      }\n      pointsDetails {\n        code\n        friendlyName\n        quantity\n        isPoolingDetail\n      }\n    }\n    pagination {\n      hasNextPage\n      pageNumber\n      index\n    }\n    error {\n      lang\n      context\n      systemService\n      systemErrorType\n      systemErrorCode\n      systemErrorMessage\n      friendlyCode\n      friendlyTitle\n      friendlyMessage\n      action {\n        number\n        buttonLabel\n        action\n      }\n    }\n  }\n}\n","variables":{"fromDate":"'.$fromDate.'","toDate":"'.$toDate.'","limit":50,"language":"en","sortOrder":"desc","pageNumber":1,"index":0,"pointType":["BASE","BONUS","SQM","BSQM","RSQM","SQS","BSQS","SQD","BSQD","EDQ"]}}'
            ];
            $json = $tab->fetch("https://akamai-gw.dbaas.aircanada.com/appsync/transaction-history-v2", $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $response = json_decode($json);
        $this->history = $response->data->transactionHistory->activityDetails ?? [];
        $this->watchdogControl->increaseTimeLimit(100);
    }

    public function parseHistory(Tab $tab, Master $master, AccountOptions $accountOptions, ParseHistoryOptions $historyOptions): void
    {
        try {
            $startDate = $historyOptions->getStartDate();
            $this->logger->debug('[History start date: ' . ($startDate ? $startDate->format('Y/m/d H:i:s') : 'all') . ']');
            $startDate = isset($startDate) ? $startDate->format('U') : 0;
            $statement = $master->getStatement() ?? $master->createStatement();

            if (empty($this->history)) {
                $this->getHistoryData($tab, $this->getHeaders($tab));
            }

            $result = [];
            $startIndex = sizeof($result);
            $result = array_merge($result, $this->parsePageHistory($startIndex, $startDate));

            foreach ($result as $activityRow) {
                $statement->addActivityRow($activityRow);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function parsePageHistory($startIndex, $startDate)
    {
        $result = [];
        foreach ($this->history as $activityDetail) {
            $dateStr = $activityDetail->date;
            $postDate = strtotime($dateStr);

            if (!$postDate) {
                $this->logger->notice("skip {$dateStr}");

                continue;
            }

            if (isset($startDate) && $postDate < $startDate) {
                $this->logger->notice("break at date {$dateStr} ($postDate)");

                break;
            }// if (isset($startDate) && $postDate < $startDate)

            $result[$startIndex]['DATE'] = $postDate;
            $result[$startIndex]['DESCRIPTION'] = $activityDetail->partnerInfo->friendlyName . " | " . $activityDetail->friendlyDescription;

            foreach ($activityDetail->pointsDetails as $pointDetail) {
                if ($pointDetail->code == 'BONUS') {
                    $result[$startIndex]['BONUS'] = $pointDetail->quantity;

                    continue;
                }

                if (!isset($result[$startIndex]['AMOUNTS'])) {
                    $result[$startIndex]['AMOUNTS'] = $pointDetail->quantity;
                } else {
                    $result[$startIndex]['AMOUNTS'] += $pointDetail->quantity;
                }
            }
            $startIndex++;
        }

        return $result;
    }

    public function parseItineraries(Tab $tab, Master $master, AccountOptions $options, ParseItinerariesOptions $parseItinerariesOptions): void
    {
        $tab->gotoUrl('https://www.aircanada.com/home/ca/en/aco/trips');
        $scriptUrl = $tab->findText('//script[contains(@src,"/home/main.")]/@src', FindTextOptions::new()->allowNull(true)->timeout(10));
        $tab->logPageState();

        if (
            empty($scriptUrl)
            && strstr($tab->getUrl(), 'viewprofile')
        ) {
            $this->logger->debug('Script url is empty. It seems that we are on wrong page. Go to itineraties page');
            $tab->gotoUrl('https://www.aircanada.com/home/ca/en/aco/trips');
            $scriptUrl = $tab->findText('//script[contains(@src,"/home/main.")]/@src', FindTextOptions::new()->allowNull(true)->timeout(10));
            $tab->logPageState();
        }

        if (empty($scriptUrl)) {
            $this->notificationSender->sendNotification('refs #25366 aeroplan - `script url is empty` // IZ');

            return;
        }

        try {
            $options = [
                'method'      => 'post',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
            ];
            $script = $tab->fetch($scriptUrl, $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $apiUrl = $this->findPreg('#"(https://\w{18,30}\.appsync-api\.(.+?)\.amazonaws\.com/graphql)"#', $script);
        $this->logger->debug("[API URL]: $apiUrl");

        if (empty($apiUrl)) {
            $this->notificationSender->sendNotification('refs #25366 aeroplan - api url is empty // IZ');

            return;
        }

        $token = $this->getToken($tab);
        $authorization = "Bearer {$token}";
        $headers = [
            'Accept-Encoding' => 'gzip, deflate, br, zstd',
            'Accept'          => '*/*',
            'Content-Type'    => 'text/plain;charset=UTF-8',
            'Origin'          => 'https://www.aircanada.com',
            'Referer'         => 'https://www.aircanada.com/',
            'Authorization'   => $authorization,
        ];
        $data = '{"query":"\n    query aeroPlanBookings {\n        getAeroplanPNRcognito(language: \"\") {\n          bookings {\n            bookingReference\n            lastName\n            departureDateTime\n          }\n          errors {\n            actions {\n                action\n                buttonLabel\n                number\n            }\n            context\n            friendlyCode\n            friendlyMessage\n            friendlyTitle\n            lang\n            systemErrorCode\n            systemErrorMessage\n            systemErrorType\n            systemService\n          }\n        }\n    }\n    "}';

        try {
            $options = [
                'method'      => 'post',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
                'body'        => $data,
            ];
            $json = $tab->fetch($apiUrl, $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $response = json_decode($json);

        if ($this->findPreg('/"getAeroplanPNRcognito":\{"bookings":\[],"/', $tab->getHtml())) {
            $master->setNoItineraries(true);

            return;
        }

        foreach ($response->data->getAeroplanPNRcognito->bookings ?? [] as $booking) {
            $this->logger->info(sprintf('Parse Itinerary #%s', $booking->bookingReference), ['Header' => 3]);
            $confNoFields = [
                'ConfNo'   => $booking->bookingReference,
                'LastName' => $booking->lastName,
            ];
            $confNoOptions = new ConfNoOptions(false);
            $tab->gotoUrl($this->getLoginWithConfNoStartingUrl($confNoFields, $confNoOptions));
            $this->watchdogControl->increaseTimeLimit(120);
            $loginWithConfNoResult = $this->loginWithConfNo($tab, $confNoFields, $confNoOptions);

            if (!$loginWithConfNoResult->isSuccess()) {
                $this->logger->error('Failed loginWithConfNo');
                continue;
            }
            $this->watchdogControl->increaseTimeLimit(240);
            $this->retrieveByConfNo($tab, $master, $confNoFields, $confNoOptions);
            $tab->gotoUrl('https://www.aircanada.com/home/ca/en/aco/trips');
            $tab->evaluate('//input[@id="bkmgMyBookings_bookingRefNumber"]', EvaluateOptions::new()->timeout(10)->allowNull(true));
        }
    }

    public function getLoginWithConfNoStartingUrl(array $confNoFields, ConfNoOptions $options): string
    {
        return 'https://www.aircanada.com/ca/en/aco/home/app.html#/retrieve?bookingRefNumber=' . $confNoFields['ConfNo'] . '&lastName=' . $confNoFields['LastName'];
    }

    public function loginWithConfNo(Tab $tab, array $confNoFields, ConfNoOptions $options): LoginWithConfNoResult
    {
        $this->logger->notice(__METHOD__);
        $el = $tab->evaluate('
            //span[contains(@class, "alert-message-text")]
            | //span[@class="pnr-number"]
        ', EvaluateOptions::new()->allowNull(true)->timeout(60));
        $tab->logPageState();

        if (!isset($el)) {
            if ($itineraryNotLoaded = $tab->findText('//div[@id="pageLoaderSrOnly"]//h2[@id="pageLoaderFooter"]', FindTextOptions::new()->allowNull(true))) {
                return LoginWithConfNoResult::error($itineraryNotLoaded);
            }

            return LoginWithConfNoResult::error('The website is experiencing technical difficulties, please try to check your balance at a later time.');
        }

        /*if (
            $el->getAttribute('class') == 'alert-message-text'
        ) {
            return LoginWithConfNoResult::error($el->getInnerText());
        }*/

        return LoginWithConfNoResult::success();
    }

    public function retrieveByConfNo(Tab $tab, Master $master, array $fields, ConfNoOptions $options): void
    {
        $this->logger->notice(__METHOD__);
        $confirmation = $tab->findText('//span[@class="pnr-number"]', FindTextOptions::new()->timeout(60)->allowNull(true));
        $tab->logPageState();

        if (!isset($confirmation)) {
            return;
        }

        $passengerNames = $tab->findTextAll('//strong[contains(@class, "pax-name")]');
        $printItineraryButton = $tab->evaluate('//button[@id="bkgdPrintItinerary"]', EvaluateOptions::new()->allowNull(true));

        if (!isset($printItineraryButton)) {
            $this->notificationSender->sendNotification('refs #25366 aeroplan - print itinerary button not found // IZ');

            return;
        }
        $printItineraryButton->click();
        $tab->logPageState();
        $f = $master->add()->flight();
        $f->general()->confirmation($confirmation, 'Booking reference');
        $f->general()->travellers($passengerNames);
        $tab->evaluate('(//section[div[contains(@class, "air-info")]])[1]', EvaluateOptions::new()->timeout(5));
        $segmentsDataElements = $tab->evaluateAll('//section[div[contains(@class, "air-info")]]');

        foreach ($segmentsDataElements as $segmentsDataElement) {
            // General info
            $airlineCode = $tab->findText('.//span[contains(@class, "flight-no-img")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $flightNumber = $tab->findText('.//span[contains(@class, "flight-no") and not(contains(@class, "img"))]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $aircraft = $tab->findText('.//a[contains(@href, "aircraft")]/span[@aria-hidden="true"]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $duration = $tab->findText('.//div[contains(@class, "air-oth")]//div[contains(text(), "h") or contains(text(), "m")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $cabin = $tab->findText('.//span[contains(text(), "Cabin")]/following-sibling::span', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            // Departure info
            $departureAirportCode = $tab->findText('.//div[contains(@class, "first-air-info")]//div[contains(@class, "airport-location")]/span[2]', FindTextOptions::new()->contextNode($segmentsDataElement));
            /*
            $departureTerminal = $tab->findText('//span[contains(@id, "flight-detail-section") and contains(@id, "origin-terminal")]', FindTextOptions::new()->contextNode($segmentsDataElement)->preg('/Terminal\s(.*)/')->allowNull(true)->nonEmptyString());
            */
            $departureDate = $tab->findText('.//../..//span[contains(@data-e2e-id, "flightblocktitle")]/../following-sibling::span[text() and not(contains(@class, "fare-family"))]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $departureTime = $tab->findText('.//div[contains(@class, "first-air-info")]//div[contains(text(), ":")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString()->preg('/[\d:]+/'));
            // Arrival info
            $arrivalAirportCode = $tab->findText('.//div[contains(@class, "air-info") and not(contains(@class, "first"))]//div[contains(@class, "airport-location")]/span[2]', FindTextOptions::new()->contextNode($segmentsDataElement));
            /*
            $arrivalTerminal = $tab->findText('//span[contains(@id, "flight-detail-section") and contains(@id, "destination-terminal")]', FindTextOptions::new()->contextNode($segmentsDataElement)->preg('/Terminal\s(.*)/')->allowNull(true)->nonEmptyString());
            */
            $arrivalDate = $tab->findText('.//../..//span[contains(@data-e2e-id, "flightblocktitle")]/../following-sibling::span[text() and not(contains(@class, "fare-family"))]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $arrivalTime = $tab->findText('.//div[contains(@class, "air-info") and not(contains(@class, "first"))]//div[contains(text(), ":")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString()->preg('/[\d:]+/'));
            /*
            if (isset($departureDate) && !isset($arrivalDate)) {
                $arrivalDate = $departureDate;
            }
            */
            $s = $f->addSegment();

            if (isset($airlineCode) && !empty($airlineCode)) {
                $s->airline()->name($airlineCode);
            }

            if (isset($flightNumber) && !empty($flightNumber)) {
                $s->airline()->number($flightNumber);
            }

            if (isset($aircraft) && !empty($aircraft)) {
                $s->extra()->aircraft($aircraft);
            }

            if (isset($duration) && !empty($duration)) {
                $s->extra()->duration($duration);
            }

            if (isset($cabin) && !empty($cabin)) {
                $s->extra()->cabin($cabin);
            }

            if (isset($departureAirportCode) && !empty($departureAirportCode)) {
                $s->departure()->code($departureAirportCode);
            }

            if (isset($departureDate, $departureTime) && !empty($departureDate) && !empty($departureTime)) {
                $s->departure()->date(DateTime::createFromFormat('D d M, Y H:i', "{$departureDate} {$departureTime}")->getTimestamp());
            }
            /*
            if (isset($departureTerminal)) {
                $s->departure()->terminal($departureTerminal);
            }
            */
            if (isset($arrivalAirportCode) && !empty($arrivalAirportCode)) {
                $s->arrival()->code($arrivalAirportCode);
            }

            if (isset($arrivalDate, $arrivalTime) && !empty($arrivalDate) && !empty($arrivalTime)) {
                $s->arrival()->date(DateTime::createFromFormat('D d M, Y H:i', "{$arrivalDate} {$arrivalTime}")->getTimestamp());
            }
            /*
            if (isset($arrivalTerminal)) {
                $s->arrival()->terminal($arrivalTerminal);
            }
            */
        }
    }

    public function parseWitoutToken(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $this->logger->notice(__METHOD__);
        $statement = $master->createStatement();
        // Name
        $name = $tab->findText('//div[contains(text(), "Legal Name")]/following-sibling::div', FindTextOptions::new()->allowNull(true)->timeout(60));
        $tab->logPageState();

        if (isset($name)) {
            $statement->addProperty('Name', $name);
        }
        // Aeroplan Number
        $number = $tab->findText('//div[contains(text(), "Aeroplan number")]/following-sibling::div', FindTextOptions::new()->allowNull(true));

        if (isset($number)) {
            $statement->addProperty('AccountNumber', $number);
        }
        // Balance - Aeroplan Points
        $balance = $tab->findText('//div[contains(text(), "Aeroplan points")]/following-sibling::div', FindTextOptions::new()->allowNull(true));

        if (isset($balance)) {
            $statement->setBalance($balance);
        }

        $tab->gotoUrl('https://www.aircanada.com/aeroplan/member/dashboard');
        $sqm = $tab->findText('//div[contains(@aria-label, "of 25,000 miles")]/p[@class="points"]', FindTextOptions::new()->allowNull(true)->timeout(60));
        $tab->logPageState();
        // Status Qualifying Miles
        if (isset($sqm)) {
            $statement->addProperty('QualifyingMiles', $sqm);
        }
        // Status Qualifying Segments
        $sqs = $tab->findText('//div[contains(@aria-label, "of") and contains(@aria-label, "segments")]/p[@class="points"]', FindTextOptions::new()->allowNull(true));

        if (isset($sqs)) {
            $statement->addProperty('QualifyingSegments', $sqm);
        }
        // Status Qualifying Dollars
        $sqd = $tab->findText('//div[contains(@aria-label, "of") and contains(@aria-label, "dollars")]/p[@class="points"]', FindTextOptions::new()->allowNull(true));

        if (isset($sqd)) {
            $statement->addProperty('QualifyingDollars', $sqm);
        }
    }

    private function getToken(Tab $tab)
    {
        $this->logger->notice(__METHOD__);
        $lastAutUser = $tab->getFromLocalStorage('CognitoIdentityServiceProvider.5put0po1jqtrfi4k9fm43roflg.LastAuthUser') ?? null;
        $key = 'CognitoIdentityServiceProvider.5put0po1jqtrfi4k9fm43roflg.' . $lastAutUser . '.accessToken';

        return $tab->getFromLocalStorage($key);
    }

    private function getHeaders(Tab $tab)
    {
        $this->logger->notice(__METHOD__);
        $token = $this->getToken($tab);
        $authorization = "Bearer {$token}";
        $headers = [
            "Accept"          => "*/*",
            "Accept-Encoding" => "gzip, deflate, br",
            "Content-Type"    => "application/json",
            "Authorization"   => $authorization,
            'x-amz-user-agent' => 'aws-amplify/5.3.0 api/1 framework/0'
        ];

        return $headers;
    }


}
