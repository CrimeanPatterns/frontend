<?php

namespace AwardWallet\Engine\virgin;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ActiveTabInterface;
use AwardWallet\ExtensionWorker\ContinueLoginInterface;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\ParseHistoryInterface;
use AwardWallet\ExtensionWorker\ParseHistoryOptions;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesOptions;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Common\Statement;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;
use AwardWallet\ExtensionWorker\LoginWithConfNoInterface;
use AwardWallet\ExtensionWorker\LoginWithConfNoResult;
use AwardWallet\ExtensionWorker\ConfNoOptions;
use AwardWallet\ExtensionWorker\RetrieveByConfNoInterface;
use CheckException;
use JMS\Serializer\Exception\Exception;

class VirginExtension extends AbstractParser implements
    LoginWithIdInterface,
    ContinueLoginInterface,
    ParseInterface,
    ParseHistoryInterface,
    ActiveTabInterface,
    ParseItinerariesInterface,
    LoginWithConfNoInterface,
    RetrieveByConfNoInterface
{
    use TextTrait;
    private const EMAIL_QUESTION = 'Please enter a temporary security code that was sent to %s. Please note that you must provide the latest code that was just sent to you.';
    private int $stepItinerary = 0;
    private array $activityDetails;
    private array $flightDetails;
    private string $firstName;
    private string $lastName;

    public function isActiveTab(AccountOptions $options): bool
    {
        return true;
    }

    public function getStartingUrl(AccountOptions $options): string
    {
        return "https://www.virginatlantic.com/flying-club/account/overview";
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $result = $tab->evaluate('//input[@id="signInName"] | //button[@aria-label="Open logged in menu"]');
        return $result->getNodeName() == 'BUTTON';
    }

    public function getLoginId(Tab $tab): string
    {
        $options = [
            'mode' => 'cors',
            'credentials' => 'same-origin',
            'method' => 'post',
            'headers' => [
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
            ],
            'body' => '{"operationName":"AccountMemberDetails","variables":{"vouchersCount":0,"activitiesCount":0,"flightsCount":0},"query":"query AccountMemberDetails($vouchersCount: Int!, $activitiesCount: Int!, $flightsCount: Int!) {\n  accountMemberDetails(\n    vouchersCount: $vouchersCount\n    activitiesCount: $activitiesCount\n    flightsCount: $flightsCount\n  ) {\n    activityResponse {\n      activityDetails {\n        activityDate\n        partnerCode\n        partnerName\n        statement\n        tierPoints\n        transactionId\n        transactionProcessedDate\n        transactionType\n        virginPoints\n        __typename\n      }\n      errorResponse {\n        extentions {\n          exception\n          code\n          __typename\n        }\n        message\n        __typename\n      }\n      __typename\n    }\n    flightDetails {\n      flightSegmentDetails {\n        bookingReference\n        dateOfFlight\n        destinationAirportCode\n        destinationAirportCountry\n        destinationAirportName\n        flightNumber\n        numberOfDaysToDeparture\n        operatingAirlineCode\n        operatingAirlineName\n        originAirportCode\n        originAirportCountry\n        originAirportName\n        scheduledDepartureTime\n        showClaimsBanner\n        __typename\n      }\n      errorResponse {\n        extentions {\n          code\n          exception\n          __typename\n        }\n        message\n        __typename\n      }\n      __typename\n    }\n    flyingClubMemberDetails {\n      memberType\n      firstName\n      lastName\n      dateofBirth\n      gender\n      email\n      contactNumber\n      countryCode\n      address {\n        addressType\n        addressLine1\n        addressLine2\n        addressLine3\n        companyName\n        city\n        postCode\n        province\n        country\n        preferredFlag\n        primary\n        countryCode\n        __typename\n      }\n      isVAAMarketingPreferred\n      headOfGroupMemberNumber\n      tierCode\n      preferredName\n      fCMembershipNumber\n      fCJoinDate\n      dateTierPointsReset\n      upgradeByDate\n      tierPoints\n      tierPointsNeedToReachNextTier\n      flownMiles\n      totalVirginPoints\n      customerID\n      retainTierPoints\n      tierPointsNeedToRetain\n      rollingTierPoints\n      memberStatus {\n        isWingsMemeber\n        isLifeTimeGoldMember\n        isMillionMiler\n        is2MillionMiler\n        __typename\n      }\n      tierThresholdDetails {\n        tierPointsForRetainToGold\n        tierPointsForRetainToSilver\n        tierPointsForRedToSilver\n        tierPointsForSilverToGold\n        __typename\n      }\n      __typename\n    }\n    voucherResponse {\n      errorResponse {\n        message\n        extentions {\n          code\n          exception\n          __typename\n        }\n        __typename\n      }\n      voucherDetails {\n        type\n        source\n        description\n        issueDate\n        expiryDate\n        status\n        number\n        isGifted\n        __typename\n      }\n      __typename\n    }\n    vouchersCount\n    activitiesCount\n    flightsCount\n    __typename\n  }\n}"}'
        ];
        try {
            $data = $tab->fetch('https://www.virginatlantic.com/flying-club/api/graphql', $options);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }
        $this->logger->info($data->body);
        $data = json_decode($data->body)->data->accountMemberDetails;
        return $data->flyingClubMemberDetails->fCMembershipNumber ?? '';
    }

    public function logout(Tab $tab): void
    {
        $tab->evaluate('//button[@aria-label="Open logged in menu"]')->click();
        $tab->evaluate('//menu[@id="log-in-menu"]//button[contains(.,"Log out")]')->click();
        sleep(1);
        $tab->evaluate('//button[@aria-label="Open logged out menu"]');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $cookies = $tab->evaluate('//button[contains(text(), "Yes, I Agree")]',
            EvaluateOptions::new()->allowNull(true)->timeout(3));
        if ($cookies) {
            $cookies->click();
            $tab->logPageState();
        }

        $tab->evaluate('//input[@id="signInName"]')->setValue($credentials->getLogin());
        $tab->evaluate('//input[@id="password"]')->setValue($credentials->getPassword());
        $tab->evaluate('//button[@id="continue"]')->click();

        $result = $tab->evaluate('//button[@aria-label="Open logged in menu"]
        | //div[@id="claimVerificationServerError" and @aria-hidden="false"]
        | //div[@id="readOnlyEmail_intro"]
        | //body/text()[contains(.,"The page cannot be displayed because an internal server error has occurred.")]',
            EvaluateOptions::new()->timeout(30)->allowNull(true));
        $tab->logPageState();
        if (!$result) {
            return new LoginResult(false);
        }
        if (stristr($result->getAttribute('aria-label'), 'Open logged in menu')) {
            return LoginResult::success();
        }
        $innerText = $result->getInnerText();
        if (stristr($innerText,
            "Click the button below and we'll send a code to verify your details.")) {
            $tab->showMessage(Message::identifyComputerSelect('Send verification code'));

            if ($this->context->isServerCheck()) {
                $tab->logPageState();
                $tab->evaluate('//button[@id="readOnlyEmail_ver_but_send"]')->click();
                $email = $tab->findText('//input[@id="readOnlyEmail"]/@value',
                    FindTextOptions::new()->timeout(30)->allowNull(true));
                if ($email) {
                    $this->logger->debug(">>> email: {$email}");
                    $question = sprintf(self::EMAIL_QUESTION, $email);
                    $this->stateManager->set('QUESTION', $question);
                    if (!$this->context->isBackground() || $this->context->isMailboxConnected()) {
                        $this->stateManager->keepBrowserSession(true);
                    }

                    return LoginResult::question($question);
                }
            } else {
                $sendCodeMessage = $tab->evaluate('//div[contains(text(),"ve sent you a code to verify your details. Please enter it into the box below.")]',
                    EvaluateOptions::new()->timeout(60)->allowNull(true));
                if ($sendCodeMessage) {
                    $tab->showMessage(Message::identifyComputer('Verify code'));
                    $verifyCodeMessage = $tab->evaluate('//button[@aria-label="Open logged in menu"]',
                        EvaluateOptions::new()->timeout(120)->allowNull(true));
                    if ($verifyCodeMessage) {
                        return LoginResult::success();
                    }
                }
                return LoginResult::identifyComputer();
            }
        } // We can't seem to find your account. Please check the details and try again.
        elseif (stristr($innerText,
                "We can't seem to find your account. Please check the details and try again.")
            || stristr($innerText,
                "Your password has expired. Click 'forgot password' below, and we'll get you back")) {
            return LoginResult::invalidPassword($innerText);
        } elseif (stristr($innerText,
                "The page cannot be displayed because an internal server error has occurred.")) {
            return LoginResult::providerError('The page cannot be displayed because an internal server error has occurred.');
        }

        return new LoginResult(false);
    }

    public function continueLogin(Tab $tab, Credentials $credentials): LoginResult
    {
        $input = $tab->evaluate('//input[@id="readOnlyEmail_ver_input"]',
            EvaluateOptions::new()->timeout(60)->allowNull(true));
        if ($input) {
            $question = $this->stateManager->get('QUESTION') ?? null;
            $this->logger->debug(">>> question: {$question}");
            $answer = $credentials->getAnswers()[$question] ?? null;
            if ($answer === null) {
                throw new CheckException("expected answer for the question");
            }

            $input->setValue($answer);
            $tab->evaluate('//button[@id="readOnlyEmail_ver_but_verify"]')->click();
            $submitResult = $tab->evaluate('//div[contains(@id, "readOnlyEmail_fail_")] 
            | //button[@aria-label="Open logged in menu"]');
            if (stristr($submitResult->getAttribute('id'), 'readOnlyEmail_fail_')) {
                return LoginResult::question($question, $submitResult->getInnerText());
            }
            if ($submitResult->getAttribute('aria-label') == 'Open logged in menu') {
                return LoginResult::success();
            }
        }
        $tab->logPageState();
        return new LoginResult(false);
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $st = $master->createStatement();
        $options = [
            'mode' => 'cors',
            'credentials' => 'same-origin',
            'method' => 'post',
            'headers' => [
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
            ],
            'body' => '{"operationName":"AccountMemberDetails","variables":{"vouchersCount":0,"activitiesCount":100,"flightsCount":50},"query":"query AccountMemberDetails($vouchersCount: Int!, $activitiesCount: Int!, $flightsCount: Int!) {\n  accountMemberDetails(\n    vouchersCount: $vouchersCount\n    activitiesCount: $activitiesCount\n    flightsCount: $flightsCount\n  ) {\n    activityResponse {\n      activityDetails {\n        activityDate\n        partnerCode\n        partnerName\n        statement\n        tierPoints\n        transactionId\n        transactionProcessedDate\n        transactionType\n        virginPoints\n        __typename\n      }\n      errorResponse {\n        extentions {\n          exception\n          code\n          __typename\n        }\n        message\n        __typename\n      }\n      __typename\n    }\n    flightDetails {\n      flightSegmentDetails {\n        bookingReference\n        dateOfFlight\n        destinationAirportCode\n        destinationAirportCountry\n        destinationAirportName\n        flightNumber\n        numberOfDaysToDeparture\n        operatingAirlineCode\n        operatingAirlineName\n        originAirportCode\n        originAirportCountry\n        originAirportName\n        scheduledDepartureTime\n        showClaimsBanner\n        __typename\n      }\n      errorResponse {\n        extentions {\n          code\n          exception\n          __typename\n        }\n        message\n        __typename\n      }\n      __typename\n    }\n    flyingClubMemberDetails {\n      memberType\n      firstName\n      lastName\n      dateofBirth\n      gender\n      email\n      contactNumber\n      countryCode\n      address {\n        addressType\n        addressLine1\n        addressLine2\n        addressLine3\n        companyName\n        city\n        postCode\n        province\n        country\n        preferredFlag\n        primary\n        countryCode\n        __typename\n      }\n      isVAAMarketingPreferred\n      headOfGroupMemberNumber\n      tierCode\n      preferredName\n      fCMembershipNumber\n      fCJoinDate\n      dateTierPointsReset\n      upgradeByDate\n      tierPoints\n      tierPointsNeedToReachNextTier\n      flownMiles\n      totalVirginPoints\n      customerID\n      retainTierPoints\n      tierPointsNeedToRetain\n      rollingTierPoints\n      memberStatus {\n        isWingsMemeber\n        isLifeTimeGoldMember\n        isMillionMiler\n        is2MillionMiler\n        __typename\n      }\n      tierThresholdDetails {\n        tierPointsForRetainToGold\n        tierPointsForRetainToSilver\n        tierPointsForRedToSilver\n        tierPointsForSilverToGold\n        __typename\n      }\n      __typename\n    }\n    voucherResponse {\n      errorResponse {\n        message\n        extentions {\n          code\n          exception\n          __typename\n        }\n        __typename\n      }\n      voucherDetails {\n        type\n        source\n        description\n        issueDate\n        expiryDate\n        status\n        number\n        isGifted\n        __typename\n      }\n      __typename\n    }\n    vouchersCount\n    activitiesCount\n    flightsCount\n    __typename\n  }\n}"}'
        ];
        $data = $tab->fetch('https://www.virginatlantic.com/flying-club/api/graphql',
            $options)->body;
        $this->logger->info($data);
        if ($this->findPreg('/exp/', $data)) {
            $this->notificationSender->sendNotification('check exp date // MI');
        }
        $data = json_decode($data)->data->accountMemberDetails;
        $this->activityDetails = $data->activityResponse->activityDetails ?? [];
        $this->flightDetails = $data->flightDetails->flightSegmentDetails ?? [];
        $this->firstName = $data->flyingClubMemberDetails->firstName;
        $this->lastName = $data->flyingClubMemberDetails->lastName;        
        $st->setBalance($data->flyingClubMemberDetails->totalVirginPoints);
        $st->addProperty('Number', $data->flyingClubMemberDetails->fCMembershipNumber);
        $st->addProperty('Name',
            "{$data->flyingClubMemberDetails->firstName} {$data->flyingClubMemberDetails->lastName}");
        $st->addProperty('MemberSince',
            strtotime($this->findPreg('/^(.+?)T/', $data->flyingClubMemberDetails->fCJoinDate)));
        $st->addProperty('TierPoints', $data->flyingClubMemberDetails->tierPoints);
        $st->addProperty('EliteStatus', $data->flyingClubMemberDetails->tierCode);
    }

    public function parseItineraries(
        Tab $tab,
        Master $master,
        AccountOptions $options,
        ParseItinerariesOptions $parseItinerariesOptions
    ): void {
        foreach ($this->flightDetails as $flight) {
            $confNoFields = [
                'ConfNo' => $flight->bookingReference,
                'FirstName' => $this->firstName,
                'LastName' => $this->lastName
            ];
            $this->logger->debug(var_export($confNoFields, true));
            $confNoOptions = new ConfNoOptions(false);
            $tab->gotoUrl($this->getLoginWithConfNoStartingUrl($confNoFields, $confNoOptions));

            $this->watchdogControl->increaseTimeLimit(120);
            $loginWithConfNoResult = $this->loginWithConfNo($tab, $confNoFields, $confNoOptions);
            if (!$loginWithConfNoResult->isSuccess()) {
                continue;
            }
            $this->watchdogControl->increaseTimeLimit(120);
            $this->retrieveByConfNo($tab, $master, $confNoFields, $confNoOptions);
        }

        /*
        if (empty($this->flightDetails)) {
            $this->notificationSender->sendNotification('refs #25513 virgin - flight details is empty // IZ');
        } else {
            $this->notificationSender->sendNotification('refs #25513 virgin - flight details is not empty // IZ');
        }
        */
    }


    public function parseHistory(
        Tab $tab,
        Master $master,
        AccountOptions $accountOptions,
        ParseHistoryOptions $historyOptions
    ): void {
        $startDate = $historyOptions->getStartDate();
        $this->logger->debug('[History start date: ' . ($startDate ? $startDate->format('Y/m/d H:i:s') : 'all') . ']');

        if (isset($startDate)) {
            $startDate = $startDate->format('U');
        } else {
            $startDate = 0;
        }
        $statement = $master->getStatement();
        $this->parsePageHistory($tab, $statement, $startDate);
    }

    public function parsePageHistory(Tab $tab, Statement $statement, $startDate)
    {
        $this->logger->debug('Total ' . count($this->activityDetails) . ' items were found');
        foreach ($this->activityDetails as $item) {
            $row = [];
            $postDate = strtotime($item->activityDate);
            if (isset($startDate) && $postDate < $startDate) {
                $this->logger->notice("break at date $item->activityDate ($postDate)");
                break;
            }
            $row['Date'] = $postDate;
            $row['Transaction Date'] = $postDate;
            $row['Activity'] = $item->statement;

            if ($this->findPreg('/Bonus/ims', $row['Activity'])) {
                $row['Bonus Mileage'] = $item->virginPoints;
            } else {
                $row['Mileage'] = $item->virginPoints;
            }
            $row['Tier points'] = $item->tierPoints;
            $statement->addActivityRow($row);
        }
    }

    public function getLoginWithConfNoStartingUrl(array $confNoFields, ConfNoOptions $options): string
    {
        return  'https://www.virginatlantic.com/my-trips/search';
    }

    /**
     * @param Tab           $tab
     * @param array         $confNoFields
     * @param ConfNoOptions $options
     * @return LoginWithConfNoResult
     */
    public function loginWithConfNo(Tab $tab, array $confNoFields, ConfNoOptions $options): LoginWithConfNoResult
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//input[@name="confirmationNo"]')->setValue($confNoFields['ConfNo']);
        $tab->evaluate('//input[@name="firstName"]')->setValue($confNoFields['FirstName']);
        $tab->evaluate('//input[@name="lastName"]')->setValue($confNoFields['LastName']);
        $tab->evaluate('//button[@id="findTripSearch"]')->click();
        $loginResult = $tab->evaluate('
            //div[@class="td-flight-route__cnfrm-no"]
            | //div[contains(@class, "mt-flight-lookup-alert_container")]//span
        ', EvaluateOptions::new()->timeout(30)->allowNull(true));

        if (!isset($loginResult)) {
            return LoginWithConfNoResult::error('');
        }

        if (
            $loginResult->getNodeName() == 'SPAN'
        ) {
            return LoginWithConfNoResult::error($loginResult->getInnerText());
        }

        return LoginWithConfNoResult::success();
    }

    public function retrieveByConfNo(Tab $tab, Master $master, array $fields, ConfNoOptions $options): void
    {
        $this->logger->notice(__METHOD__);
        $this->parseItinerary($tab, $master);
    }

    public function parseItinerary(Tab $tab, Master $master): void
    {
        $this->logger->notice(__METHOD__);
        $this->watchdogControl->increaseTimeLimit(120);
        $confirmation = $tab->findText('//div[@class="td-flight-route__cnfrm-no"]', FindTextOptions::new()->timeout(20)->allowNull(true));
        $tab->logPageState();
        $f = $master->add()->flight();
        if (!isset($confirmation)) {
            return;
        }
        $f->general()->confirmation($confirmation, 'Booking reference');

        // departure info
        $departureDate = $tab->findText('//idp-flight-card-header//idp-departure-arrival-info[div[div[contains(text(), "Depart")]]]//div[contains(@class, "td-flight-point-date") and text()]', FindTextOptions::new()->allowNull(true));
        $departureTime = $tab->findText('//idp-flight-card-header//idp-departure-arrival-info[div[div[contains(text(), "Depart")]]]//div[contains(@class, "td-flight-point-time") and text()]', FindTextOptions::new()->allowNull(true));

        $departureAirportCode = $tab->findText('//idp-flight-card-header//idp-departure-arrival-info[div[div[contains(text(), "Depart")]]]//*[contains(@class, "td-train-point-city") or contains(@class, "td-fc-dept-arr-airport")]', FindTextOptions::new()->preg('/\((.*)\)/')->allowNull(true));
        $departureTerminal = $tab->findText('//idp-flight-card-header//idp-departure-arrival-info[div[div[contains(text(), "Depart")]]]//span[contains(@class, "td-flight-point-terminal")]', FindTextOptions::new()->allowNull(true));

        // arrival info
        $arrivalDate = $tab->findText('//idp-flight-card-header//idp-departure-arrival-info[div[div[contains(text(), "Arrive")]]]//div[contains(@class, "td-flight-point-date") and text()]', FindTextOptions::new()->allowNull(true));
        $arrivalTime = $tab->findText('//idp-flight-card-header//idp-departure-arrival-info[div[div[contains(text(), "Arrive")]]]//div[contains(@class, "td-flight-point-time") and text()]', FindTextOptions::new()->allowNull(true));

        $arrivalAirportCode = $tab->findText('//idp-flight-card-header//idp-departure-arrival-info[div[div[contains(text(), "Arrive")]]]//*[contains(@class, "td-train-point-city") or contains(@class, "td-fc-dept-arr-airport")]', FindTextOptions::new()->preg('/\((.*)\)/')->allowNull(true));
        $arrivalTerminal = $tab->findText('//idp-flight-card-header//idp-departure-arrival-info[div[div[contains(text(), "Arrive")]]]//span[contains(@class, "td-flight-point-terminal")]', FindTextOptions::new()->allowNull(true));

        $aircraftName = $tab->findText('//span[contains(@class, "td-flight-name")]//a[contains(@href, "airport-guides")]', FindTextOptions::new()->allowNull(true));
        $airlineCode = $tab->findText('//span[@class="td-flight-number"]/a', FindTextOptions::new()->allowNull(true)->preg('/[A-z]+/'));
        $flightNumber = $tab->findText('//span[@class="td-flight-number"]/a', FindTextOptions::new()->allowNull(true)->preg('/\d+/'));
        $duration = $tab->findText('//div[contains(@class, "td-flight-total-duration")]', FindTextOptions::new()->preg('/Duration(.*)/')->allowNull(true));
        $cabin = $tab->findText('//span[contains(@class, "td-cabin-name")]/span', FindTextOptions::new()->preg('/\((.*)\)/')->allowNull(true));

        if ($openPassengersData = $tab->evaluate('//a[@id="toggleFlightDetailsButton"]', EvaluateOptions::new()->allowNull(true))) {
            $openPassengersData->click();
            $passengerNames = $tab->findTextAll('//span[contains(@class, "td-passenger__title__name")]');
            if (!empty($passengerNames)) {
                $f->general()->travellers($passengerNames);
            }
        }

        $s = $f->addSegment();
        if (isset($airlineCode)) {
            $s->airline()->name($airlineCode);
        }
        if (isset($flightNumber)) {
            $s->airline()->number($flightNumber);
        }
        if (isset($aircraftName)) {
            $s->extra()->aircraft($aircraftName);
        }
        if (isset($duration)) {
            $s->extra()->duration($duration);
        }
        if (isset($cabin)) {
            $s->extra()->cabin($cabin);
        }
        if (isset($departureAirportCode)) {
            $s->departure()->code($departureAirportCode);
        }
        if (isset($departureDate, $departureTime)) {
            $s->departure()->date2("{$departureDate} {$departureTime}");
        }
        if (isset($departureTerminal)) {
            $s->departure()->terminal($departureTerminal);
        }
        if (isset($arrivalAirportCode)) {
            $s->arrival()->code($arrivalAirportCode);
        }
        if (isset($arrivalDate, $arrivalTime)) {
            $s->arrival()->date2("{$arrivalDate} {$arrivalTime}");
        }
        if (isset($arrivalTerminal)) {
            $s->arrival()->terminal($arrivalTerminal);
        }        
    }
}
