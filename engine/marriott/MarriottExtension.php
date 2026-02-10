<?php

namespace AwardWallet\Engine\marriott;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ContinueLoginInterface;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;
use AwardWallet\ExtensionWorker\ParseHistoryInterface;
use AwardWallet\ExtensionWorker\ParseHistoryOptions;
use AwardWallet\ExtensionWorker\ParseItinerariesInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesOptions;
use CheckException;
use Symfony\Component\DomCrawler\Crawler;

class MarriottExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ParseHistoryInterface, ParseItinerariesInterface, ContinueLoginInterface
{
    use TextTrait;
    public const HISTORY_PAGE_URL = "https://www.marriott.com/loyalty/myAccount/activity.mi?activityType=types&monthsFilter=24";
    protected $accountActivity = [];
    private ?string $customerId;
    protected $endHistory = false;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.marriott.com/loyalty/myAccount/default.mi';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('//input[contains(@id, "email")] 
        | //span[contains(@class, "t-label-xs") and contains(@class, "t-label-alt-xs") and not(contains(@class, "member-title"))]
        | //a[@data-dialog-id="member-panel-dialog"]');

        return $el->getNodeName() == "SPAN" || $el->getNodeName() == "A";
    }

    public function getLoginId(Tab $tab): string
    {
        $html = $tab->getHtml();
        return $this->findPreg("/\"mr_id\":\s*\"([^\"]+)/", $html)
            ?? $this->findPreg("/\"rewardsId\":\s*\"([^\"]+)/", $html);
        /*return $tab->evaluate('//span[contains(@class, "t-label-xs") and contains(@class, "t-label-alt-xs") and not(contains(@class, "member-title"))]',
            EvaluateOptions::new()->nonEmptyString())->getInnerText();*/
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        /**
         * //button[@id="remember_me"] - "Sign in with different account" button
         * //div[contains(@class, "username-input") and not(contains(@class, "disabled"))] - login input not disabled
         */

        $tab->evaluate('
            //button[@id="remember_me"] 
            | //div[contains(@class, "username-input") and not(contains(@class, "disabled"))]
        ', EvaluateOptions::new()->allowNull(True)->timeout(10));
        sleep(1);
        $clear = $tab->evaluate('
            //button[@id="remember_me"] 
            | //div[contains(@class, "username-input") and not(contains(@class, "disabled"))]
        ', EvaluateOptions::new()->allowNull(True)->timeout(10));

        if (
            isset($clear) &&
            strstr($clear->getAttribute('id'), "remember_me")
        ) {
            $clear->click();
            //$login = $tab->evaluate('//input[contains(@id, "email") and not(@readonly)]');
            sleep(3);
        }

        $login = $tab->evaluate('//input[contains(@id, "email") and not(@readonly)]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[contains(@id, "password")]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//button[@data-testid="sign-in-btn-submit"]')->click();

        $submitResult = $tab->evaluate('
            //span[contains(@class, "t-label-xs") and contains(@class, "t-label-alt-xs") and not(contains(@class, "member-title"))]/../h4 
            | //div[contains(@data-testid, "input-error")] | //span[contains(@class, "error-label")]
            | //div[@role="alert"]/p
            | //button[@data-testid="send-code-btn"]
            | //a[@data-dialog-id="member-panel-dialog"]
        ', EvaluateOptions::new()->timeout(30));

        if ($submitResult->getNodeName() == 'H4' || $submitResult->getNodeName() == 'A') {
            return new LoginResult(true);
        } elseif ($submitResult->getNodeName() == 'P') {
            $error = $submitResult->getInnerText();

            if (
                strstr($error, "Please correct the following and try again: Email/member number and/or password. ")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($error, "Our apologies – sign-in is temporarily not available. We’re working to resolve this. Please try again later.")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_PROVIDER_ERROR);
            }

            return new LoginResult(false, $error);
        } elseif (in_array($submitResult->getNodeName(), ["DIV", "SPAN"])) {
            return new LoginResult(false, $submitResult->getInnerText(), null, ACCOUNT_INVALID_PASSWORD);
        } else {
            $tab->showMessage('To continue, please select the method for receiving a one-time code and click the "Send Code" button.');

            if ($this->context->isServerCheck()) {
                //$this->notificationSender->sendNotification('success question // MI');

                $tab->logPageState();
                $sendCodeChoice = $tab->evaluate('//label[./span[contains(., "Email code to")]]',
                    EvaluateOptions::new()->timeout(30));
                if ($sendCodeChoice) {
                    $sendCodeChoice->click();
                    $tab->evaluate('//button[contains(.,"Send Code")]')->click();
                    $question = $tab->findText('//div[contains(text(), "We sent a six-digit code via email to")]',
                        FindTextOptions::new()->timeout(30));

                    if (!$this->context->isBackground() || $this->context->isMailboxConnected()) {
                        $this->stateManager->keepBrowserSession(true);
                    }

                    return LoginResult::question($question);
                }
            } else {
                if (!$tab->evaluate('//button[@data-testid="verify-button"]',
                    EvaluateOptions::new()->timeout(60)->allowNull(true))) {
                    return LoginResult::identifyComputer();
                };
                $tab->showMessage('Please enter the received one-time code and click the "Verify" button to continue.');

                $otpSubmitResult = $tab->evaluate('//span[contains(@class, "t-label-xs") and contains(@class, "t-label-alt-xs") and not(contains(@class, "member-title"))]
                | //a[@data-dialog-id="member-panel-dialog"]',
                    EvaluateOptions::new()->timeout(180)->allowNull(true));

                if (!$otpSubmitResult) {
                    return LoginResult::identifyComputer();
                } else {
                    return new LoginResult(true);
                }
            }
        }
        return new LoginResult(true);
    }

    public function continueLogin(Tab $tab, Credentials $credentials): LoginResult
    {
        $question = $tab->findText('//div[contains(text(), "We sent a six-digit code via email to")]',
            FindTextOptions::new()->timeout(10));
        if ($question) {
            $answer = $credentials->getAnswers()[$question] ?? null;
            if ($answer === null) {
                throw new \CheckException("expected answer for the question");
            }

            $input = $tab->evaluate('//input[@name="input-text-Verification Code"]');
            $input->setValue($answer);
            $tab->evaluate('//button[contains(.,"Verify")]')->click();
            $submitResult = $tab->evaluate('//div[@id="verify-your-code-form-error"] | //span[contains(@class, "t-label-xs") and contains(@class, "t-label-alt-xs") and not(contains(@class, "member-title"))]/../h4 ');

            if ($submitResult->getAttribute('id') === 'verify-your-code-form-error') {
                return LoginResult::question($question, $submitResult->getInnerText());
            }
            if ($submitResult->getNodeName() == 'H4' || $submitResult->getNodeName() == 'A') {
                return LoginResult::success();
            }
        }
        $tab->logPageState();
        return new LoginResult(false);
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl('https://marriott.com/aries-auth/logout.comp');
        $tab->evaluate('//div[@data-testid="signout"] | //h1[contains(text(), "Something happened")]/following-sibling::p
        | //button[contains(@aria-label,"Sign In")]');
        if ($error = $tab->findText('//h1[contains(text(), "Something happened")]/following-sibling::p', FindTextOptions::new()->allowNull(true))) {
            throw new CheckException($error, ACCOUNT_PROVIDER_ERROR);
        }
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
//        if ($this->context->isServerCheck()) {
//            $this->notificationSender->sendNotification('success login // MI');
//        }
        $statement = $master->createStatement();
        $tab->logPageState();

        // 2fa wrong redirect workaround
        if ($tab->getUrl() == 'https://www.marriott.com/default.mi') {
            $tab->gotoUrl("https://www.marriott.com/loyalty/myAccount/default.mi");
        }

        $sessionToken = $this->findPreg("/\"sessionId\":\s*\"([^\"]+)/", $tab->getHtml());
        // Qualification Period
        $statement->addProperty('YearBegins', strtotime("1 JAN"));
        // Nights this year
        $nights = $tab->findText("//div[contains(text(), 'NIGHTS THIS YEAR')]/preceding-sibling::div[1] | //div[@data-testid=\"nightsdetail\"]//div[a[contains(text(), \"Nights This Year\")]]/preceding-sibling::div", FindTextOptions::new()->allowNull(true)->timeout(10)->preg('/([0-9]+)/ims'));
        if (isset($nights)) {
            $statement->addProperty("Nights", $nights);
        }

        if (!isset($statement->getProperties()['Nights'])) {
            $statement->addProperty("Nights", $this->findPreg("/\"mr_prof_nights_booked_this_year\":\s*\"([^\"]+)/", $tab->getHtml()));
        }

        // Member since
        $memberSince = $tab->findText("//p[contains(text(), 'Member since')]", FindTextOptions::new()->allowNull(true)->preg('/since\s*([^<]+)/ims'));
        if (isset($memberSince)) {
            $statement->addProperty('MemberSince', $memberSince);            
        }

        if (!isset($statement->getProperties()['MemberSince'])) {
            $statement->addProperty("MemberSince", $this->findPreg("/\"mr_prof_join_date\":\s*\"([^\-\"]+)/", $tab->getHtml()));
        }

        // Rewards #
        $statement->addProperty("Number",
            $this->findPreg("/\"mr_id\":\s*\"([^\"]+)/", $tab->getHtml())
            ?? $this->findPreg("/\"rewardsId\":\s*\"([^\"]+)/", $tab->getHtml())
        );
        
        // Name
        if ($name = $this->findPreg("/\"mr_prof_name_full\":\s*\"([^\"]+)/", $tab->getHtml())) {
            $statement->addProperty("Name", beautifulName($name));
        }

        // refs #16853
        // Ambassador Qualifying Dollars
        $ambassadorQualifyingDollars = $tab->findText('//div[contains(@class, "elite_main_guage")]//p[contains(text(), "this year")]', FindTextOptions::new()->allowNull(true)->preg('/\+\s*(.+)\s+USD this year/ims'));
        $statement->addProperty("AmbassadorQualifyingDollars", isset($ambassadorQualifyingDollars) ? '$' . $ambassadorQualifyingDollars : '');
        // Ambassador Qualifying Nights
        $ambassadorQualifyingNights = $tab->findText('//div[contains(@class, "elite_main_guage")]//p[contains(text(), "this year")]', FindTextOptions::new()->allowNull(true)->preg('/^\s*(\d+)\s*Night/ims'));
        if (isset($ambassadorQualifyingNights)) {
            $statement->addProperty("AmbassadorQualifyingNights", $ambassadorQualifyingNights);
        }

        /**
         * We’re temporarily unable to display the information you requested.
         * Please try again later.
         */
        if (!$statement->getBalance()) {
            if ($message = $tab->findText('//div[contains(@id, "NightDetails")]//p[contains(text(), "We’re temporarily unable to display the information you requested.")]', FindTextOptions::new()->allowNull(true))) {
                throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
            }
            // Something happened
            if ($message = $tab->findText('//h1[contains(text(), "Something happened")]', FindTextOptions::new()->allowNull(true))) {
                throw new CheckException('The website is experiencing technical difficulties, please try to check your balance at a later time.', ACCOUNT_PROVIDER_ERROR);
            }
        }// if ($this->ErrorCode == ACCOUNT_ENGINE_ERROR)

        // Lifetime Membership  // refs #16853, refs #16853, https://redmine.awardwallet.com/issues/16930#note-15
        $lifetimeMembership = $tab->findText("//*[self::span or self::h2][contains(text(), 'You are Lifetime') or contains(text(), 'You’ve earned Lifetime')]", FindTextOptions::new()->allowNull(true)->preg('/Lifetime\s*([^<]+)/ims'));
        if (isset($lifetimeMembership)) {
            $statement->addProperty("LifetimeMembership", $lifetimeMembership);
        }

        if (isset($this->Properties['LifetimeMembership'])) {
            $statement->addProperty('LifetimeMembership', str_replace(' status', '', $statement->getProperties()['LifetimeMembership']));
            $this->logger->info('Lifetime properties', ['Header' => 3]);
            // Your lifetime statistics
            $tab->gotoUrl("https://www.marriott.com/loyalty/myAccount/lifeTimeNightDetails.mi?_=" . date("UB"));
            // Nights (Lifetime Nights)
            $lifetimeNights = $tab->findText("//p[contains(text(), 'Nights:')]", FindTextOptions::new()->allowNull(true)->preg('/:\s*(\d+)/ims')->timeout(10));
            if (isset($lifetimeNights)) {
                $statement->addProperty("LifetimeNights", $lifetimeNights);
            }
            // Years as Silver, Gold or Platinum
            $yearsAsSilver = $tab->findText("//p[contains(text(), 'Years as silver, Gold or Platinum:')]", FindTextOptions::new()->allowNull(true)->preg('/:\s*(\d+)/ims'));
            if (isset($yearsAsSilver)) {
                $statement->addProperty("YearsAsSilver", $yearsAsSilver);
            }
            // Years as Gold or Platinum
            $yearsAsGold = $tab->findText("//p[contains(text(), 'Years as Gold or Platinum:')]", FindTextOptions::new()->allowNull(true)->preg('/:\s*(\d+)/ims'));
            if (isset($yearsAsGold)) {
                $statement->addProperty("YearsAsGold", $yearsAsGold);
            }
            // Years as Platinum
            $yearsAsPlatinum = $tab->findText("//p[contains(text(), 'Years as Platinum:')]", FindTextOptions::new()->allowNull(true)->preg('/:\s*(\d+)/ims'));
            if (isset($yearsAsPlatinum)) {
                $statement->addProperty("YearsAsPlatinum", $yearsAsPlatinum);
            }
        }

        $tab->gotoUrl(self::HISTORY_PAGE_URL);
        $this->logger->info('Expiration date', ['Header' => 3]);
        // Expiration date      // refs #10278
        $lastActivity = $tab->findText("//div[contains(@class, 't-activity-heading') and contains(., 'last qualifying activity')]", FindTextOptions::new()->allowNull(true)->preg("/was\s*([^\.]+)/")->timeout(10));
        if (isset($lastActivity) && strtotime($lastActivity)) {
            $statement->addProperty("LastActivity", strtotime($lastActivity));
        }
        // Your account will remain active and points won’t expire as long as you stay or use some of your points by ...
        // or
        // Don't let your points expire. Stay with us or use some of your points by ... to keep your account active.
        $exp = $tab->findText('(//p[contains(text(), "expire as long as you stay") or contains(text(), "Don\'t let your points expire.")])[1]', FindTextOptions::new()->allowNull(True)->preg("/oints\s*by\s*([^\.\s]+)/"));
        $this->logger->debug("Exp date: {$exp}");

        if ($exp = strtotime($exp)) {
            $statement->SetExpirationDate($exp);
        }
        // refs #16930, https://redmine.awardwallet.com/issues/16930#note-4
        else {
            if (
                !isset($statement->getProperties()['LastActivity'])
                && ($lastActivity = $tab->findText("//div[contains(@class, \"tile-activity-grid\")]//div[contains(@class, \"l-row\") and not(contains(@class, \"headers\")) and
                    (
                    div[*[contains(@class, 'l-description')]
                           and not(contains(., 'POINTS TRANSFERRED'))
                           and not(contains(., 'POINTS ADDED VIA TRANSFER'))
                           and not(contains(., 'POINT TRANSFER WITH OTHER ACCOUNT'))
                           and not(contains(., 'MRPoints'))
                           and not(contains(., 'MarriottBonvoyPoints'))
                           and not(contains(., 'Points Reinstatement'))
                           and not(contains(., 'Points Expiration'))
                           and not(contains(., 'Cancelled 0 Rewards'))
                       ]
                    or p[contains(@class, 'l-description')
                           and not(contains(., 'POINTS TRANSFERRED'))
                           and not(contains(., 'POINTS ADDED VIA TRANSFER'))
                           and not(contains(., 'POINT TRANSFER WITH OTHER ACCOUNT'))
                           and not(contains(., 'MRPoints'))
                           and not(contains(., 'MarriottBonvoyPoints'))
                           and not(contains(., 'Points Reinstatement'))
                           and not(contains(., 'Points Expiration'))
                           and not(contains(., 'Cancelled 0 Rewards'))
                       ]
                   )
                ][1]/p[contains(@class, 'post-date')]",
                FindTextOptions::new()->allowNull(true)))
            ) {// div[p[contains(@class, 'l-description')]] | div[a[contains(@class, 'l-description')]]
                // p[contains(@class, 'l-description')]
                if (strtotime($lastActivity)) {                    
                    $statement->addProperty("LastActivity", strtotime($lastActivity));
                }
            }
            // refs #16853, https://redmine.awardwallet.com/issues/16930#note-15
            if (!empty($statement->getProperties()['LifetimeMembership'])) {
                $statement->setNeverExpires(true);
                /*
                $this->ClearExpirationDate();
                */
                $statement->addProperty("ClearExpirationDate", "Y");
                $statement->addProperty("AccountExpirationWarning", "do not expire with elite status");
            }
            // refs #16930, https://redmine.awardwallet.com/issues/16930#note-41
            elseif ($tab->findText("//div[contains(@class, 't-activity-heading') and contains(., 'last qualifying activity')]", FindTextOptions::new()->preg("/was\s*([^\.]+)/")->allowNull(true))) {
                $lastActivity = $tab->findText("//div[contains(@class, 't-activity-heading') and contains(., 'last qualifying activity')]", FindTextOptions::new()->preg("/was\s*([^\.]+)/")->allowNull(true));
                $exp = strtotime($lastActivity);
                // https://redmine.awardwallet.com/issues/16930#note-21
                $exp = strtotime("+24 month", $exp);

                // TODO: refs #16930, https://help.marriott.com/s/article/Article-24119
                $statement->addProperty("AccountExpirationWarning", 'The balance on this award program due to expire on ' . date("m/d/Y", $exp) . '
<br />
<br />
Marriott Rewards on their website state that <a href="https://www.marriott.com/loyalty/terms/default.mi" target="_blank">&quot;Members must remain active in the Loyalty Program to retain Points they accumulate. If a Member Account is inactive for twenty-four (24) consecutive months, that Member Account will forfeit all accumulated Points. Members can remain active in the Loyalty Program and retain accumulated Points by earning Points or Miles in the Loyalty Program or redeeming Points in the Loyalty Program at least once every twenty-four (24) months, subject to the exceptions described below&quot;</a>.
<br />
<br />
i. Not all Points activities help maintain active status in the Loyalty Program. The following activities do not count toward maintaining an active status in the Loyalty Program:
<br />
A. Gifting or transferring Points; however, converting Points to Miles or Miles to Points does count toward maintaining an active status;<br />
B. Receiving Points as a gift or transfer; or
<br />
C. Earning Points through social media programs, such as #MarriottBonvoyPoints.
<br />
<br />
We determined that your latest valid activity date was on ' . $lastActivity . ', so the expiration date for your account balance was calculated by adding 24 months to this date.');
                $statement->SetExpirationDate($exp);
            }
        }

        // refs #23625
        if ($consumerID = $this->findPreg('/consumerID\":\"([^"]+)/', $tab->getHtml())) {
            $this->customerId = $consumerID;
            $headers = [
                "Accept"                       => "*/*",
                "Accept-Language"              => "en-US",
                "Accept-Encoding"              => "gzip, deflate, br",
                "Content-Type"                 => "application/json",
            ];
            $data = '{"sessionToken":"'.$sessionToken.'","context":{"context":{"localeKey":"en_GB"}}}';
            try {
                $options = [
                    'method'  => 'post',
                    'headers' => $headers,
                    'body' => $data
                ];
                $json = $tab->fetch('https://www.marriott.com/hybrid-presentation/api/v1/getUserDetails', $options)->body;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return;
            }
            $this->logger->info($json);
            $userDetails = json_decode($json) ?? [];
    
            // Name
            if (!empty($userDetails->headerSubtext->consumerName)) {
                $statement->addProperty('Name', $userDetails->headerSubtext->consumerName);
            }
            // Level
            if (!empty($userDetails->userProfileSummary->level)) {
                $statement->addProperty('Level', $userDetails->userProfileSummary->level);
            }
            // Lifetime Titanium Elite
            if (!empty($userDetails->userProfileSummary->eliteLifetimelevelDescription)) {
                $statement->addProperty('LifetimeMembership', beautifulName(str_replace('Lifetime ', '', $userDetails->userProfileSummary->eliteLifetimelevelDescription)));
            }
            // Balance
            if (empty($statement->getBalance())) {
                $this->logger->notice("set balance from json");
                $statement->SetBalance($userDetails->userProfileSummary->currentPoints);                
            }

            $headers = [
                "Accept"                       => "*/*",
                "Accept-Language"              => "en-US",
                "Accept-Encoding"              => "gzip, deflate, br",
                "Content-Type"                 => "application/json",
                "apollographql-client-name"    => "phoenix_account",
                "apollographql-client-version" => "v1",
                "x-request-id"                 => "phoenix_account-d3acd6cf-7441-46d4-b683-50e5878c255e",
                "application-name"             => "account",
                "graphql-require-safelisting"  => "true",
                "graphql-operation-signature"  => "bdf6686a7fc0fbf318133a1a286d992d96470e80605aae3d1154d6e827aa3ecb",
            ];
            $data = '{"operationName":"phoenixAccountGetMyActivityTable","variables":{"customerId":"' . $consumerID . '","numberOfMonths":25,"types":"all","limit":1000,"offset":0,"filter":null},"query":"query phoenixAccountGetMyActivityTable($customerId: ID!, $numberOfMonths: Int, $limit: Int, $sort: String, $offset: Int, $types: String, $filter: AccountActivityFilterInput) {\n  customer(id: $customerId) {\n    loyaltyInformation {\n      accountActivity(\n        numberOfMonths: $numberOfMonths\n        limit: $limit\n        sort: $sort\n        offset: $offset\n        types: $types\n        filter: $filter\n      ) {\n        total\n        edges {\n          node {\n            postDate\n            ... on LoyaltyAccountActivity {\n              totalEarning\n              baseEarning\n              eliteEarning\n              extraEarning\n              isQualifyingActivity\n              actions {\n                actionDate\n                totalEarning\n                type {\n                  code\n                  description\n                  __typename\n                }\n                __typename\n              }\n              currency {\n                code\n                __typename\n              }\n              partner {\n                account\n                type {\n                  code\n                  description\n                  __typename\n                }\n                __typename\n              }\n              __typename\n            }\n            type {\n              code\n              description\n              __typename\n            }\n            description\n            ... on LoyaltyAccountAwardActivity {\n              awardType {\n                code\n                __typename\n              }\n              __typename\n            }\n            startDate\n            endDate\n            properties {\n              id\n              policies {\n                daysFolioAvailableOnline\n                __typename\n              }\n              basicInformation {\n                bookable\n                brand {\n                  id\n                  __typename\n                }\n                name\n                nameInDefaultLanguage\n                __typename\n              }\n              __typename\n            }\n            __typename\n          }\n          __typename\n        }\n        pageInfo {\n          hasNextPage\n          hasPreviousPage\n          previousOffset\n          currentOffset\n          nextOffset\n          __typename\n        }\n        __typename\n      }\n      rewards {\n        lastQualifyingActivityDate\n        datePointsExpire\n        vistanaPointsExpired\n        number\n        isExempt\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"}';

            try {
                $options = [
                    'method'  => 'post',
                    'headers' => $headers,
                    'body' => $data
                ];
                $json = $tab->fetch('https://www.marriott.com/mi/query/phoenixAccountGetMyActivityTable', $options)->body;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return;
            }

            $this->logger->info($json);
            $response = json_decode($json) ?? [];
            $this->accountActivity = $response->data->customer->loyaltyInformation->accountActivity->edges ?? [];

            // Expiration date      // refs #10278
            if (isset($response->data->customer->loyaltyInformation->rewards->lastQualifyingActivityDate)) {
                if (strtotime($response->data->customer->loyaltyInformation->rewards->lastQualifyingActivityDate)) {
                    $statement->addProperty("LastActivity", strtotime($response->data->customer->loyaltyInformation->rewards->lastQualifyingActivityDate));
                }
            }
            // Your account will remain active and points won’t expire as long as you stay or use some of your points by ...
            // or
            // Don't let your points expire. Stay with us or use some of your points by ... to keep your account active.
            $exp = $response->data->customer->loyaltyInformation->rewards->datePointsExpire ?? null;
            $this->logger->debug("Exp date: {$exp}");

            if ($exp = strtotime($exp)) {
                $statement->SetExpirationDate($exp);
            }

            // refs #16853, https://redmine.awardwallet.com/issues/16930#note-15
            if (!empty($this->Properties['LifetimeMembership'])) {
                $statement->setNeverExpires(true);
                /*
                $this->ClearExpirationDate();
                */
                $statement->addProperty("ClearExpirationDate", "Y");
                $statement->addProperty("AccountExpirationWarning", "do not expire with elite status");
            }

            // subAccounts - Unused Rewards Certificates
            $this->logger->info('Unused Rewards Certificates', ['Header' => 3]);

            $headers = [
                "Accept"                       => "*/*",
                "Accept-Language"              => "en-US",
                "Accept-Encoding"              => "gzip, deflate, br",
                "Content-Type"                 => "application/json",
                "apollographql-client-name"    => "phoenix_account",
                "apollographql-client-version" => "v1",
                "x-request-id"                 => "phoenix_account-146bd539-dba6-45f4-8580-3e1f4995b005",
                "application-name"             => "account",
                "graphql-require-safelisting"  => "true",
                "graphql-operation-signature"  => "6972f5f1f0e93b306f14c3c7d5cb5a6f14b3f567eece51a6b0d18db2cc8e3d7c",
            ];
            $data = '{"operationName":"phoenixAccountGetMyActivityRewardsEarned","variables":{"customerId":"' . $consumerID . '"},"query":"query phoenixAccountGetMyActivityRewardsEarned($customerId: ID!) {\n  customer(id: $customerId) {\n    loyaltyInformation {\n      suiteNightAwards {\n        available {\n          count\n          details {\n            issueDate\n            expirationDate\n            count\n            __typename\n          }\n          __typename\n        }\n        expired {\n          count\n          details {\n            issueDate\n            expirationDate\n            count\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      certificates {\n        total\n        edges {\n          node {\n            awardType {\n              code\n              label\n              description\n              enumCode\n              __typename\n            }\n            expirationDate\n            isCancellable\n            issueDate\n            numberOfNights\n            points\n            __typename\n          }\n          __typename\n        }\n        status {\n          ... on ResponseStatus {\n            __typename\n            code\n            httpStatus\n            messages {\n              user {\n                type\n                id\n                field\n                message\n                details\n                __typename\n              }\n              ops {\n                type\n                id\n                field\n                message\n                details\n                __typename\n              }\n              dev {\n                type\n                id\n                field\n                message\n                details\n                __typename\n              }\n              __typename\n            }\n          }\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"}';


            try {
                $options = [
                    'method'  => 'post',
                    'headers' => $headers,
                    'body' => $data
                ];
                $json = $tab->fetch('https://www.marriott.com/mi/query/phoenixAccountGetMyActivityRewardsEarned', $options)->body;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return;
            }

            $this->logger->info($json);
            $response = json_decode($json) ?? [];
            $edges = $response->data->customer->loyaltyInformation->certificates->edges ?? [];
            $this->logger->debug("Total " . count($edges) . " Unused Rewards Certificates were found");
            $subAccounts = [];
            $statement->addProperty("CombineSubAccounts", false);

            foreach ($edges as $edge) {
                $subAcc = [];
                $displayName = $edge->node->awardType->description;
                $subAcc['ExpirationDate'] = strtotime($edge->node->expirationDate);
                $code = "marriott" . md5(str_replace(' ', '', $displayName)) . $subAcc['ExpirationDate'];
                $subAcc['Code'] = $code;
                $subAcc['DisplayName'] = "Certificate: " . $displayName;
                $subAcc['Balance'] = null;
                $subAccounts = $this->groupCertificates($subAccounts, $subAcc);
            }// foreach ($edges as $edge)

            $this->logger->info('Nightly Upgrade Awards', ['Header' => 3]);

            $suiteNightAwards = $response->data->customer->loyaltyInformation->suiteNightAwards->available->details ?? [];
            $this->logger->debug("Total " . count($suiteNightAwards) . " Nightly Upgrade Awards were found");

            foreach ($suiteNightAwards as $suiteNightAward) {
                $subAcc = [];
                $displayName = "Nightly Upgrade Awards - {$suiteNightAward->count} ";
                $subAcc['ExpirationDate'] = strtotime($suiteNightAward->expirationDate);
                $code = "marriottNightlyUpgradeAwards" . md5(str_replace(' ', '', $displayName)) . $subAcc['ExpirationDate'];
                $subAcc['Code'] = $code;
                $subAcc['DisplayName'] = $displayName . ($suiteNightAward->count == 1 ? "Night" : "Nights");
                $subAcc['Balance'] = null;
                $subAccounts = $this->groupCertificates($subAccounts, $subAcc);
            }// foreach ($suiteNightAwards as $suiteNightAward)

            foreach ($subAccounts as $subAccount) {
                $statement->AddSubAccount($subAccount);
            }


            // refs #23909
            $tab->gotoUrl('https://www.marriott.com/loyalty/myAccount/nights.mi');
            // Continue as Silver Elite: Stay 10 more nights by December 31.
            $nightsNeededToRetainTier = $tab->findText('//span[contains(text(),"Continue as ")]/following-sibling::span', FindTextOptions::new()->allowNull(true)->preg('/Stay (\d+) more nights/')->timeout(10));
            if (isset($nightsNeededToRetainTier)) {
                $statement->addProperty("NightsNeededToRetainTier", $nightsNeededToRetainTier);
            }
            // Unlock more benefits: Stay 25 more nights by December 31 to reach Gold Elite.
            $nightsUntilNextTier = $tab->findText('//span[contains(text(),"Unlock more benefits:")]/following-sibling::span', FindTextOptions::new()->allowNull(true)->preg('/Stay (\d+) more nights/'));
            if ($nightsUntilNextTier) {
                $statement->addProperty("NightsUntilNextTier", $nightsUntilNextTier);
            }
            // 250 Total Nights + 5 years as Silver Elite or higher
            $totalNightsReach = $tab->findText('//p[contains(text(),"Total Nights +")]', FindTextOptions::new()->allowNull(true)->preg('/(\d+) Total Nights \+/'));
            $totalNights = $tab->findText('//p[contains(text(),"Total Nights:")]/span', FindTextOptions::new()->allowNull(true)->preg('/^(\d+)$/'));
            if (isset($totalNightsReach, $totalNights)) {
                $statement->addProperty("NightsUntilNextLifetimeTier", $totalNightsReach - $totalNights);
            }

            // 250 Total Nights + 5 years as Silver Elite or higher
            $years = $tab->findText('//p[contains(text(),"Total Nights +")]', FindTextOptions::new()->allowNull(true)->preg('/\+ (\d+) years as/'));
            $yearsSilver = $tab->findText('//p[contains(text(),"Years as Silver Elite:")]/span', FindTextOptions::new()->allowNull(true)->preg('/^(\d+)$/'));
            $yearsGold = $tab->findText('//p[contains(text(),"Years as Gold Elite:")]/span', FindTextOptions::new()->allowNull(true)->preg('/^(\d+)$/'));
            $yearsPlatinum = $tab->findText('//p[contains(text(),"Years as Platinum Elite:")]/span', FindTextOptions::new()->allowNull(true)->preg('/^(\d+)$/'));
            if (isset($years, $yearsSilver, $yearsGold, $yearsPlatinum)) {
                $sumYears = $years - $yearsSilver - $yearsGold - $yearsPlatinum;
                if ($sumYears > 0) {
                    $statement->addProperty("StatusYearsUntilNextLifetimeTier", $sumYears);
                }
            }


            // refs#24688
            $headers['x-request-id'] = 'phoenix_account-be00d1d4-d238-4ad8-a3fb-568cbbb63500';
            $headers['graphql-operation-signature'] = '5f22916f33ab30251e999ff2957d31dd9245416d695292631a83076e5dc1496d';
            $data = '{"operationName":"phoenixAccountGetMemberStatusDetails","variables":{"customerId":"' . $consumerID . '","startYear":2024},"query":"query phoenixAccountGetMemberStatusDetails($customerId: ID!, $startYear: Int) {\n  customer(id: $customerId) {\n    profile {\n      name {\n        givenName\n        __typename\n      }\n      __typename\n    }\n    contactInformation {\n      emails {\n        address\n        primary\n        __typename\n      }\n      phones {\n        country {\n          code\n          __typename\n        }\n        number\n        __typename\n      }\n      __typename\n    }\n    loyaltyInformation {\n      rewards {\n        currentPointBalance\n        eliteLifetimeNights\n        eliteLifetimeLevel {\n          code\n          __typename\n        }\n        level {\n          code\n          __typename\n        }\n        levelType {\n          code\n          __typename\n        }\n        nextLevelType {\n          code\n          __typename\n        }\n        nextLevel {\n          code\n          __typename\n        }\n        number\n        eliteNightsToAchieveNext\n        eliteNightsToRenewCurrent\n        datePointsExpire\n        __typename\n      }\n      rewardsSummary {\n        yearly(startYear: $startYear) {\n          totalNights\n          stayNights\n          year\n          totalRevenue\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"}';

            try {
                $options = [
                    'method'  => 'post',
                    'headers' => $headers,
                    'body' => $data
                ];
                $json = $tab->fetch('https://www.marriott.com/mi/query/phoenixAccountGetMemberStatusDetails', $options)->body;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return;
            }

            $this->logger->info($json);
            $response = json_decode($json) ?? [];

            foreach ($response->data->customer->loyaltyInformation->rewardsSummary->yearly ?? [] as $item) {
                if (
                    $item->__typename === 'RewardYearlySummary'
                    && !empty($item->totalRevenue)
                    && isset($item->totalRevenue)
                ) {
                    // Annual Qualifying Spend: 142 of $23,000 USD
                    $statement->addProperty("AnnualQualifyingSpend", $item->totalRevenue);
                    break;
                }
            }

            return;
        }

        $nodes = $tab->evaluateAll('//div[contains(@class, "tile-unused-certificates")]//li[p[starts-with(normalize-space(text()), "Expires")] or p[span[starts-with(normalize-space(text()), "Expires")]]]');
        $nodesCount = count($nodes);
        $this->logger->debug("Total {$nodesCount} Unused Rewards Certificates were found");
        $subAccounts = [];

        if ($nodesCount > 0) {
            $statement->addProperty("CombineSubAccounts", false);

            for ($i = 0; $i < $nodesCount; $i++) {
                $subAcc = [];
                $displayName =
                    $tab->findText("p[contains(@class, 'l-display-block')]/span/span", FindTextOptions::new()->allowNull(true)->contextNode($nodes[$i]))
                    ?? $tab->findText("p[contains(@class, 'l-display-block')]/span", FindTextOptions::new()->allowNull(true)->contextNode($nodes[$i]))
                    ?? $tab->findText("p/span[contains(@class, 'l-display-block')]", FindTextOptions::new()->allowNull(true)->contextNode($nodes[$i]))
                ;
                $subAcc['ExpirationDate'] = strtotime($tab->findText("p[starts-with(normalize-space(text()), 'Expires')] | p/span[starts-with(normalize-space(text()), 'Expires')]", FindTextOptions::new()->contextNode($nodes[$i])->preg('/Expires:?\s+(.+)/')));
                $code = "marriott" . md5(str_replace(' ', '', $displayName)) . $subAcc['ExpirationDate'];
                $subAcc['Code'] = $code;
                $subAcc['DisplayName'] = "Certificate: " . $displayName;
                $subAcc['Balance'] = null;
            }// for ($i = 0; $i < $nodes->length; $i++)

            foreach ($subAccounts as $subAccount) {
                $statement->AddSubAccount($subAccount);
            }
        }// if ($nodes->length > 0)
    }

    public function groupCertificates($subAccounts, $subAcc)
    {
        $this->logger->notice(__METHOD__);
        $result = [];
        $duplicate = false;
        $this->logger->debug("Adding Certificate...");
        $this->logger->debug(var_export($subAcc, true), ['pre' => true]);

        if (empty($subAccounts)) {
            $subAccounts[] = $subAcc;
        } else {
            foreach ($subAccounts as $subAccount) {
                if (isset($subAccount['Code']) && $subAccount['Code'] == $subAcc['Code']) {
                    $duplicate = true;

                    if ($subAccount['Balance'] == null) {
                        $subAccount['Balance'] = 2;
                    } else {
                        $subAccount['Balance']++;
                    }
                }// if (isset($subAccount['Code']) && $subAccount['Code'] == $subAcc['Code'])
                $result[] = $subAccount;
            }// foreach($this->Properties['DetectedCards'] as $subAccount)
            $subAccounts = $result;

            if (!$duplicate) {
                $subAccounts[] = $subAcc;
            }
        }
        $this->logger->debug("Certificates:");
        $this->logger->debug(var_export($subAccounts, true), ['pre' => true]);

        return $subAccounts;
    }

    public function parseHistory(Tab $tab, Master $master, AccountOptions $accountOptions, ParseHistoryOptions $historyOptions) : void
    {
        try {
            $result = [];
            $startDate = $historyOptions->getStartDate();
            $this->logger->debug('[History start date: ' . ($startDate ? $startDate->format('Y/m/d H:i:s') : 'all') . ']');
            $startDate = isset($startDate) ? $startDate->format('U') : 0;
            $tab->gotoUrl(self::HISTORY_PAGE_URL);
            $dataFromJson = $tab->findText('//script[contains(text(), "window.MI_S2_RESOURCE_BASE_URL")]',
                FindTextOptions::new()->preg("/window.makenComponents\s*=\s*(.+)\/\/\]\]>$/")->allowNull(true));

            $page = 0;
            //		do {
            $page++;
            $this->logger->debug("[Page: {$page}]");
            //			if ($page > 1) {
            //				$this->http->NormalizeURL($url);
            //				$this->http->GetURL($url);
            //			}
            $startIndex = sizeof($result);
            $resultFromHTML = array_merge($result, $this->ParseHistoryPage($startIndex, $tab, $startDate));

            if ($dataFromJson) {
                $result = array_merge($result, $this->ParseHistoryJSON($dataFromJson, $startIndex, $startDate, $tab));
            }

            // refs #18481 - if history to small, only several transactions
            if (!$dataFromJson || (count($resultFromHTML) > count($result))) {
                $this->logger->notice("grab info from html");
                $result = $resultFromHTML;
            }

            // refs #23625
            if (!$dataFromJson && !empty($this->accountActivity)) {
                $this->logger->debug("Total " . count($this->accountActivity) . " history items were found");

                foreach ($this->accountActivity as $activity) {
                    $dateStr = $activity->node->startDate;
                    $postDate = strtotime($dateStr);

                    if (isset($startDate) && $postDate < $startDate) {
                        $this->logger->notice("break at date {$dateStr} ($postDate)");
                        $this->endHistory = true;

                        continue;
                    }
                    $result[$startIndex]['Date Posted'] = $postDate;
                    $result[$startIndex]['Type'] = $activity->node->type->description ?? null;
                    $result[$startIndex]['Description'] = $activity->node->description ?? null;

                    $points = $activity->node->totalEarning ?? null;

                    if ($this->findPreg('/Bonus/ims', $result[$startIndex]['Type'])) {
                        $result[$startIndex]['Bonus points earned'] = $points;
                    } else {
                        $result[$startIndex]['Points earned'] = $points;
                    }

                    $startIndex++;
                }
            }

            foreach ($result as $activityRow) {
                $master->getStatement()->addActivityRow($activityRow);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function ParseHistoryJSON($dataFromJson, $startIndex, $startDate = null, Tab $tab)
    {
        $this->logger->notice(__METHOD__);
        $result = [];
        $dataFromJson = json_decode($dataFromJson);

        if (!isset($value)) {
            $this->logger->error("activityFilters not found");

            return [];
        }

        $data = json_decode($value);
        $recordsPerPage = $tab->findText('//select[@id = "viewPerPage"]/option[contains(text(), "All")]/@value');

        if (!$recordsPerPage) {
            $this->logger->error("recordsPerPage not found");

            return [];
        }
        $data["context"]["recordsPerPage"] = $recordsPerPage;
        $data['sourceURI'] = "/loyalty/myAccount/activity.mi";
        $data['sessionToken'] = $this->findPreg("/\"sessionId\":\s*\"([^\"]+)/", $tab->getHtml());
        $this->logger->debug(var_export($data, true), ['pre' => true]);

        $headers = [
            "Accept"           => "application/json, text/javascript, */*; q=0.01",
            "Content-Type"     => "application/json",
            "X-Requested-With" => "XMLHttpRequest",
        ];

        try {
            $options = [
                'method'  => 'post',
                'headers' => $headers,
                'body' => json_encode($data)
            ];
            $json = $tab->fetch('https://www.marriott.com/aries-rewards/v1/activityList.comp', $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $this->logger->info($json);
        $response = json_decode($json) ?? [];
        $activitiesList = $response->component->data->activitiesList ?? [];
        $this->logger->debug("Total " . count($activitiesList) . " history items were found");

        foreach ($activitiesList as $activity) {
            $dateStr = $activity->postDate;
            $postDate = strtotime($dateStr);

            if (isset($startDate) && $postDate < $startDate) {
                $this->logger->notice("break at date {$dateStr} ($postDate)");
                $this->endHistory = true;

                continue;
            }
            $result[$startIndex]['Date Posted'] = $postDate;
            $result[$startIndex]['Type'] = $this->findPreg("/([^*]+)/", $activity->activityType);
            $result[$startIndex]['Description'] = $activity->description ?? null;

            if (isset($activity->descNextLine)) {
                $result[$startIndex]['Description'] = trim($result[$startIndex]['Description'] . ' ' . $activity->descNextLine);
            }
            $points = $this->findPreg('#([\d\.\,\-]+)#ims', $activity->points);

            if ($this->findPreg('/Bonus/ims', $result[$startIndex]['Type'])) {
                $result[$startIndex]['Bonus points earned'] = $points;
            } else {
                $result[$startIndex]['Points earned'] = $points;
            }
            $startIndex++;
        }

        return $result;
    }

    public function ParseHistoryPage($startIndex, Tab $tab, $startDate = null)
    {
        $this->logger->notice(__METHOD__);
        $result = [];
        $tab->evaluate('(//div[contains(@class, "tile-activity-grid")]//div[contains(@class, "l-row") and not(contains(@class, "headers"))])[1]',
            EvaluateOptions::new()->allowNull(true)->timeout(10));
        $nodes = $tab->evaluateAll('//div[contains(@class, "tile-activity-grid")]//div[contains(@class, "l-row") and not(contains(@class, "headers"))]');
        $nodesCount = count($nodes);
        $this->logger->debug("Total {$nodesCount} history items were found");

        for ($i = 0; $i < $nodesCount; $i++) {
            $node = $nodes[$i];
            $dateStr = $tab->findText("p[contains(@class, 'post-date')]", FindTextOptions::new()->allowNull(True)->contextNode($node));
            if (!isset($dateStr)) {
                $this->logger->debug('date not found, skipping');
                continue;
            }
            $postDate = strtotime($dateStr);

            if (isset($startDate) && $postDate < $startDate) {
                $this->logger->notice("break at date {$dateStr} ($postDate)");
                $this->endHistory = true;

                continue;
            }
            $result[$startIndex]['Date Posted'] = $postDate;
            $result[$startIndex]['Type'] = $tab->findText("p[contains(@class, 'activity-type')]", FindTextOptions::new()->preg("/([^*]+)/")->allowNull(true)->contextNode($node));
            $result[$startIndex]['Description'] = $tab->findText("div[p[contains(@class, 'l-description')]] | div[a[contains(@class, 'l-description')]]", FindTextOptions::new()->allowNull(true)->contextNode($node));

            if (!$result[$startIndex]['Description']) {
                $result[$startIndex]['Description'] = $tab->findText("p[contains(@class, 'l-description')]", FindTextOptions::new()->allowNull(true)->contextNode($node));
            }
            $points = $tab->findText(".//p[contains(@class, 'l-points')]", FindTextOptions::new()->allowNull(true)->preg('#([\d\.\,\-]+)#ims'));

            if ($this->findPreg('/Bonus/ims', $result[$startIndex]['Type'])) {
                $result[$startIndex]['Bonus points earned'] = $points;
            } else {
                $result[$startIndex]['Points earned'] = $points;
            }
            
            $startIndex++;
        }

        return $result;
    }

    public function parseItineraries(Tab $tab, Master $master, AccountOptions $options, ParseItinerariesOptions $parseItinerariesOptions): void
    {
        if (!isset($this->customerId)) {
            $this->logger->error("customerId empty");
            return;
        }
        $this->watchdogControl->increaseTimeLimit(300);
        $result = [];
        $headers = [
            "Accept"                       => "*/*",
            "Accept-Language"              => "en-US",
            "Accept-Encoding"              => "gzip, deflate, br",
            "Content-Type"                 => "application/json",
            "apollographql-client-name"    => "phoenix_account",
            "apollographql-client-version" => "v1",
            "x-request-id"                 => "phoenix_account-d3acd6cf-7441-46d4-b683-50e5878c255e",
            "application-name"             => "account",
            "graphql-require-safelisting"  => "true",
            "graphql-operation-signature"  => "21eb6e0c22a6c2b5fc1b5c34c6ac2e335cb02e9c5a47ff22e8445ccc7b6ba9f2",
        ];
        $offset = 0;

        do {
            $this->logger->debug("[Offset: $offset]");
            $data = '{"operationName":"phoenixAccountGetUpcomingTripsOfCustomer","variables":{"customerId":"'.$this->customerId.'","status":"ACTIVE","limit":10,"offset":'.$offset.'},"query":"query phoenixAccountGetUpcomingTripsOfCustomer($customerId: ID!, $status: OrderStatus, $limit: Int, $offset: Int) {\n  customer(id: $customerId) {\n    orders(status: $status, limit: $limit, offset: $offset) {\n      edges {\n        node {\n          id\n          items {\n            basicInformation {\n              startDate\n              endDate\n              confirmationNumber\n              __typename\n            }\n            property {\n              id\n              __typename\n            }\n            awardRequests {\n              status {\n                code\n                __typename\n              }\n              type {\n                code\n                __typename\n              }\n              __typename\n            }\n            stay {\n              status {\n                code\n                description\n                enumCode\n                label\n                __typename\n              }\n              stayStatus {\n                code\n                description\n                __typename\n              }\n              __typename\n            }\n            id\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      total\n      __typename\n    }\n    __typename\n  }\n}\n"}';
            try {
                $requestOptions = [
                    'method'  => 'post',
                    'headers' => $headers,
                    'body' => $data
                ];
                $json = $tab->fetch('https://www.marriott.com/mi/query/phoenixAccountGetUpcomingTripsOfCustomer', $requestOptions)->body;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                return;
            }

            $this->logger->info($json);
            $response = json_decode($json);
            $edges = $response->data->customer->orders->edges ?? [];
            $this->logger->info(sprintf('Found %s itineraries', count($edges)));
            if ($offset == 0 && count($edges) == 0 && $this->findPreg('/,"edges":\[],"total":0/', $tab->getHtml())) {
                $master->setNoItineraries(true);
                return;
            }

            $offset += 10;
            foreach ($edges as $edge) {
                $item = $edge->node->items[0];
                $confirmationNumber = $item->basicInformation->confirmationNumber;
                $propertyId = $item->property->id;
                $tab->gotoUrl("https://www.marriott.com/reservation/findReservationDetail.mi?confirmationNumber={$confirmationNumber}&tripId={$edge->node->id}&propertyId={$confirmationNumber}");

                try {
                    $requestOptions = [
                        'method'  => 'get',
                        'headers' => $headers,
                    ];
                    $body = $tab->fetch("https://www.marriott.com/mi/phoenix-book-preprocessor/v1/findReservationDetail?confirmationNumber={$confirmationNumber}&tripId={$confirmationNumber}&propertyId={$propertyId}", $requestOptions)->body;
                    $crawler = new Crawler($body);
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                    return;
                }
    
                $this->getItinerary($confirmationNumber, $propertyId, $tab, $master, $crawler);
            }

            if ($offset >= 10) {
                // refs #24949
                if (in_array($options->login, ['116231932'])) {
                    $this->logger->debug('special case of account 116231932; increaseTimeLimit -> 100');
                    $this->watchdogControl->increaseTimeLimit(100);
                } else {
                    $this->logger->debug('increaseTimeLimit -> 60');
                    $this->watchdogControl->increaseTimeLimit(100);
                }
            }
        } while ($offset < 100 && count($edges) > 0);

        // return $result;
    }

    private function getItinerary($confirmationNumber, $propertyId, Tab $tab, Master $master, Crawler $crawler) {
        $this->logger->notice(__METHOD__);

        $miDataLayer = $crawler->filterXPath("//script[@id='miDataLayer']");
        if (!isset($miDataLayer)) {
            $this->logger->error('dataLayer script empty');
            return;
        }
        $miDataLayer = $this->findPreg('/var dataLayer = (.+?); var mvpOffers =/', $miDataLayer->text());
        $miDataLayer = json_decode($miDataLayer);
        if (!isset($miDataLayer)) {
            $this->logger->error('dataLayer json empty');
            return;
        }
        $headers = [
            "Accept" => "*/*",
            "Content-Type" => "application/json",
            "Accept-Language" => "en-US",
            "Accept-Encoding" => "gzip, deflate, br",
            "apollographql-client-name" => "phoenix_book",
            "apollographql-client-version" => "1",
            "graphql-operation-name" => "PhoenixBookHotelHeaderData",
            "graphql-require-safelisting" => "true",
            "graphql-operation-signature" => "018b971a06886d6d2ca3f77747bf9f9c2cc8d49638bf25a1c3429277c4627c9f",
        ];
        $data = '{"operationName":"PhoenixBookHotelHeaderData","variables":{"propertyId":"' . $propertyId . '"},"query":"query PhoenixBookHotelHeaderData($propertyId: ID!) {\n  property(id: $propertyId) {\n    id\n    basicInformation {\n      latitude\n      longitude\n      name\n      brand {\n        id\n        __typename\n      }\n      __typename\n    }\n    reviews {\n      numberOfReviews {\n        count\n        description\n        __typename\n      }\n      stars {\n        count\n        description\n        __typename\n      }\n      __typename\n    }\n    contactInformation {\n      contactNumbers {\n        number\n        type {\n          description\n          code\n          __typename\n        }\n        __typename\n      }\n      address {\n        line1\n        line2\n        line3\n        city\n        stateProvince {\n          description\n          __typename\n        }\n        country {\n          description\n          code\n          __typename\n        }\n        postalCode\n        __typename\n      }\n      __typename\n    }\n    ... on Hotel {\n      seoNickname\n      __typename\n    }\n    media {\n      primaryImage {\n        edges {\n          node {\n            imageUrls {\n              wideHorizontal\n              __typename\n            }\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"}';

        try {
            $requestOptions = [
                'method'  => 'post',
                'headers' => $headers,
                'body'    => $data
            ];
            $json = $tab->fetch("https://www.marriott.com/mi/query/PhoenixBookHotelHeaderData", $requestOptions)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $this->logger->info($json);
        $hotel = json_decode($json);

        $headers = [
            "Accept" => "*/*",
            "Content-Type" => "application/json",
            "Accept-Language" => "en-US",
            "Accept-Encoding" => "gzip, deflate, br",
            "apollographql-client-name" => "phoenix_book",
            "apollographql-client-version" => "1",
            "graphql-operation-name" => "PhoenixBookAuthRoomOverviewDetails",
            "graphql-require-safelisting" => "true",
            "graphql-operation-signature" => "3f603341dcff4d9757011a1c67c09e42dfd174b0c94a98b86e561ac94709b277",
        ];
        $data = '{"operationName":"PhoenixBookAuthRoomOverviewDetails","variables":{"orderId":"' . $confirmationNumber . '","customerId":"' . $this->customerId . '"},"query":"query PhoenixBookAuthRoomOverviewDetails($orderId: ID!, $customerId: ID!) {\n  order(id: $orderId) {\n    items {\n      stay {\n        estimatedTimeOfArrival\n        __typename\n      }\n      comments {\n        comment\n        __typename\n      }\n      addOns {\n        category {\n          code\n          description\n          label\n          __typename\n        }\n        ordinal\n        code: id\n        description\n        addOnStatus: status {\n          code\n          description\n          label\n          __typename\n        }\n        __typename\n      }\n      basicInformation {\n        product {\n          ... on HotelRoom {\n            rates {\n              name\n              localizedName {\n                translatedText\n                __typename\n              }\n              __typename\n            }\n            roomAttributes {\n              attributes {\n                description\n                category {\n                  code\n                  __typename\n                }\n                __typename\n              }\n              __typename\n            }\n            __typename\n          }\n          __typename\n        }\n        Id\n        confirmationNumber\n        isCancellable\n        isModifiable\n        inHouse\n        isOTA\n        isRedemption\n        lengthOfStay\n        oldRates\n        creationDate\n        __typename\n      }\n      totalPricing {\n        childAges\n        quantity\n        numberInParty\n        numberOfAdults\n        numberOfChildren\n        rateAmountsByMode {\n          pointsPerQuantity {\n            points\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      product {\n        id\n        ... on HotelRoom {\n          media {\n            photoTour {\n              images {\n                captions {\n                  title\n                  __typename\n                }\n                metadata {\n                  imageFile\n                  __typename\n                }\n                __typename\n              }\n              __typename\n            }\n            __typename\n          }\n          availableAddOns {\n            addOns {\n              numberOfRequestSlots\n              id\n              startDate\n              endDate\n              description\n              category {\n                code\n                label\n                description\n                __typename\n              }\n              status {\n                code\n                label\n                description\n                __typename\n              }\n              __typename\n            }\n            __typename\n          }\n          basicInformation {\n            name\n            oldRates\n            startDate\n            endDate\n            type\n            description\n            __typename\n          }\n          termsAndConditions {\n            rules {\n              descriptions\n              type {\n                code\n                description\n                __typename\n              }\n              __typename\n            }\n            __typename\n          }\n          rateDetails {\n            id\n            ratePlanType {\n              code\n              __typename\n            }\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      property {\n        ... on Hotel {\n          policies {\n            smokefree\n            petsPolicyDescription\n            localizedPetsPolicyDescription {\n              translatedText\n              __typename\n            }\n            petsAllowed\n            petsPolicyDetails {\n              additionalPetFee\n              additionalPetFeeType\n              refundableFee\n              refundableFeeType\n              nonRefundableFee\n              nonRefundableFeeType\n              __typename\n            }\n            __typename\n          }\n          __typename\n        }\n        basicInformation {\n          currency\n          isMarshaProperty\n          gmtOffset\n          __typename\n        }\n        transportation {\n          type {\n            code\n            __typename\n          }\n          name\n          __typename\n        }\n        __typename\n      }\n      id\n      guests {\n        primaryGuest {\n          name {\n            givenName\n            surname\n            title {\n              code\n              label\n              description\n              __typename\n            }\n            __typename\n          }\n          emails {\n            address\n            primary\n            __typename\n          }\n          phones {\n            number\n            type {\n              code\n              label\n              description\n              __typename\n            }\n            __typename\n          }\n          addresses {\n            type {\n              code\n              label\n              description\n              __typename\n            }\n            primary\n            line1\n            line2\n            city\n            stateProvince\n            postalCode\n            country {\n              code\n              label\n              description\n              __typename\n            }\n            __typename\n          }\n          __typename\n        }\n        rewardsNumber\n        __typename\n      }\n      awardRequests {\n        type {\n          code\n          description\n          __typename\n        }\n        status {\n          code\n          description\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    id\n    __typename\n  }\n  customer(id: $customerId) {\n    revisionToken\n    loyaltyInformation {\n      rewards {\n        number\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n}\n"}';

        try {
            $requestOptions = [
                'method'  => 'post',
                'headers' => $headers,
                'body'    => $data
            ];
            $json = $tab->fetch("https://www.marriott.com/mi/query/PhoenixBookAuthRoomOverviewDetails", $requestOptions)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $this->logger->info($json);
        $room = json_decode($json);

        $headers = [
            "Accept" => "*/*",
            "Content-Type" => "application/json",
            "Accept-Language" => "en-US",
            "Accept-Encoding" => "gzip, deflate, br",
            "apollographql-client-name" => "phoenix_book",
            "apollographql-client-version" => "1",
            "graphql-operation-name" => "PhoenixBookSummaryOfChargesAuth",
            "graphql-require-safelisting" => "true",
            "graphql-operation-signature" => "afc0a549aac2274802eb139547eafa359702049c236b7480708a8bf182caf612",
        ];
        $data = '{"operationName":"PhoenixBookSummaryOfChargesAuth","variables":{"orderId":"' . $confirmationNumber . '","customerId":"' . $this->customerId . '","propertyId":"phxps"},"query":"query PhoenixBookSummaryOfChargesAuth($orderId: ID!, $customerId: ID!, $propertyId: ID!) {\n  order(id: $orderId) {\n    items {\n      product {\n        ... on HotelRoom {\n          rateDetails {\n            segment\n            startDate\n            lengthOfStay\n            rateAmounts {\n              amount {\n                origin {\n                  value: amount\n                  valueDecimalPoint\n                  __typename\n                }\n                __typename\n              }\n              freeNights\n              freeNightsPoints\n              points\n              rateMode {\n                code\n                __typename\n              }\n              __typename\n            }\n            name\n            isFreeNight\n            ratePlanType {\n              code\n              __typename\n            }\n            isGuestViewable\n            isRedemption\n            fnaTopOffPoints\n            certificateNumber\n            __typename\n          }\n          __typename\n        }\n        __typename\n      }\n      totalPricing {\n        quantity\n        rateAmounts {\n          amount {\n            origin {\n              value: amount\n              valueDecimalPoint\n              __typename\n            }\n            __typename\n          }\n          rateMode {\n            code\n            __typename\n          }\n          points\n          __typename\n        }\n        fees {\n          rateAmounts {\n            rateUnit {\n              code\n              description\n              __typename\n            }\n            amount {\n              origin {\n                value: amount\n                valueDecimalPoint\n                __typename\n              }\n              __typename\n            }\n            __typename\n          }\n          costType {\n            description\n            __typename\n          }\n          __typename\n        }\n        isGuestViewable\n        __typename\n      }\n      basicInformation {\n        confirmationNumber\n        lengthOfStay\n        isRedemption\n        __typename\n      }\n      property {\n        basicInformation {\n          currency\n          __typename\n        }\n        __typename\n      }\n      __typename\n    }\n    rateAmounts {\n      amount {\n        origin {\n          value: amount\n          valueDecimalPoint\n          currency\n          __typename\n        }\n        __typename\n      }\n      points\n      __typename\n    }\n    isGuestViewable\n    __typename\n  }\n  customer(id: $customerId) {\n    loyaltyInformation {\n      rewards {\n        currentPointBalance\n        __typename\n      }\n      __typename\n    }\n    __typename\n  }\n  property(id: $propertyId) {\n    transportation {\n      type {\n        code\n        __typename\n      }\n      name\n      __typename\n    }\n    __typename\n  }\n}\n"}';

        try {
            $requestOptions = [
                'method'  => 'post',
                'headers' => $headers,
                'body'    => $data
            ];
            $json = $tab->fetch("https://www.marriott.com/mi/query/PhoenixBookSummaryOfChargesAuth", $requestOptions)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return;
        }

        $this->logger->info($json);
        $summary = json_decode($json);
        $this->parseItinerary($miDataLayer, $hotel, $room, $summary, $master);
    }

    public function parseItinerary($miDataLayer, $hotel, $room, $summary, Master $master)
    {
        $this->logger->notice(__METHOD__);

        $hotel = $hotel->data->property ?? null;
        $items = $room->data->order->items ?? [];

        if (empty($items)) {
            $this->logger->error("checkin info not found");

            return;
        }
        $h = $master->add()->hotel();

        $confNo = [];
        foreach ($items as $room) {
            if (isset($room->product)) {
                if (!empty($room->product->basicInformation->name)) {
                    $r = $h->addRoom();
                    $r->setDescription("{$room->product->basicInformation->name} {$room->product->basicInformation->description}");
                }
                $h->general()->confirmation($room->basicInformation->confirmationNumber, "Confirmation Number");
                $confNo[] = $room->basicInformation->confirmationNumber;
            }
        }
        $this->logger->info('Parse Itinerary #' . join(', ', $confNo), ['Header' => 3]);

        $h->booked()->guests($miDataLayer->numberOfAdults ?? null, false, true);
        //$h->general()->confirmation($confNo, "Confirmation Number", true);

        $guests = $items[0]->guests ?? [];

        foreach ($guests as $guest) {
            $h->general()->traveller(beautifulName("{$guest->primaryGuest->name->givenName} {$guest->primaryGuest->name->surname}"));
        }

        // Address
        $address = $hotel->contactInformation->address->line1 ?? null;
        if (isset($hotel->contactInformation->address->city)) {
            $address .= ", {$hotel->contactInformation->address->city}";
        }
        if (isset($hotel->contactInformation->address->postalCode)) {
            $address .= ", {$hotel->contactInformation->address->postalCode}";
        }
        if (isset($hotel->contactInformation->address->country->description)) {
            $address .= ", {$hotel->contactInformation->address->country->description}";
        }
        $h->hotel()
            ->name($hotel->basicInformation->name ?? null)
            ->address($address);

        $contactNumbers = $hotel->contactInformation->contactNumbers ?? [];

        foreach ($contactNumbers as $phone) {
            if ($phone->type->code == 'phone') {
                $h->hotel()->phone($phone->number);
            }
            if ($phone->type->code == 'fax') {
                $h->hotel()->fax($phone->number);
            }
        }

        $h->booked()
            ->checkIn2($items[0]->product->basicInformation->startDate ?? $items[1]->product->basicInformation->startDate)
            ->checkOut2($items[0]->product->basicInformation->endDate ?? $items[1]->product->basicInformation->endDate);

        $currency = $tax = $total = $valueDecimalPoint = null;
        if (isset($summary->data)) {
            $items = $summary->data->order->items ?? [];
            foreach ($items as $item) {
                $currency = $item->property->basicInformation->currency;
                foreach ($item->totalPricing->rateAmounts as $rateAmount) {
                    if ($rateAmount->rateMode->code == 'advance-purchase-amount') {
                        $total += $rateAmount->amount->origin->value;
                    }
                    if ($rateAmount->rateMode->code == 'total-taxes-per-quantity') {
                        $tax += $rateAmount->amount->origin->value;
                        $valueDecimalPoint = $rateAmount->amount->origin->valueDecimalPoint;
                    }
                }
            }
        }

        if (isset($currency, $valueDecimalPoint)) {
            $h->price()->currency($currency);
            $precision = pow(10, $valueDecimalPoint);
            $h->price()->total(round($total / $precision, 2));
            $h->price()->tax(round($tax / $precision, 2));
        }


        $this->logger->debug('Parsed itinerary:');
        $this->logger->debug(var_export($h->toArray(), true), ['pre' => true]);
    }    
}