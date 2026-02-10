<?php

namespace AwardWallet\Engine\british;

use AwardWallet\Common\Parsing\Exception\ProfileUpdateException;
use AwardWallet\Common\Parsing\Web\Captcha\RucaptchaProvider;
use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ActiveTabInterface;
use AwardWallet\ExtensionWorker\CommunicationException;
use AwardWallet\ExtensionWorker\ConfNoOptions;
use AwardWallet\ExtensionWorker\ContinueLoginInterface;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithConfNoInterface;
use AwardWallet\ExtensionWorker\LoginWithConfNoResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\MessageType;
use AwardWallet\ExtensionWorker\ParseHistoryInterface;
use AwardWallet\ExtensionWorker\ParseHistoryOptions;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesOptions;
use AwardWallet\ExtensionWorker\QuerySelectorOptions;
use AwardWallet\ExtensionWorker\RetrieveByConfNoInterface;
use AwardWallet\ExtensionWorker\SelectFrameOptions;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Common\Statement;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;
use CheckException;

class BritishExtension extends AbstractParser implements
    ActiveTabInterface,
    LoginWithIdInterface,
    ParseInterface,
    ParseItinerariesInterface,
    ParseHistoryInterface,
    LoginWithConfNoInterface,
    RetrieveByConfNoInterface,
    ContinueLoginInterface
{
    use TextTrait;

    private $activityIgnore = [
        "Expired Avios",
        "Points Reset for New Membership Year",
        "Combine My Avios",
        "Manual Avios Adjustment",
        "Redemption Redeposit",
        "Avios Adjustment",
        "Tier Points Adjustment",
    ];
    private $itCount = 0;
    private string $countryUrl;
    private $ignoreBookings = [];

    public function getStartingUrl(AccountOptions $options): string
    {
        //return $this->getCountryUrl('https://www.britishairways.com/travel/home/public/%s', $options);
        $this->countryUrl = $this->getCountryUrl(
            'https://www.britishairways.com/travel/viewaccount/execclub/_gf/%s',
            $options
        );
        // https://www.britishairways.com/nx/b/customerhub/en/gbr/your-account?tab=your-trips
        return $this->countryUrl;
    }

    public function getCountryUrl($url, AccountOptions $options): string
    {
        $country = $options->login2;

        if (strlen($country) > 0) {
            $country = "en_$country";
        } else {
            $country = "en_us";
        }

        return sprintf($url, strtolower($country));
    }

    private function acceptAll(Tab $tab)
    {
        $this->logger->debug(__METHOD__);
        $acceptAll = $tab->evaluate('//button[@aria-label="Accept All"]',
            EvaluateOptions::new()->timeout(0)->allowNull(true));

        if ($acceptAll) {
            $acceptAll->click();
        }
    }

    public function isLoggedIn(Tab $tab): bool
    {
        sleep(3);
        $this->acceptAll($tab);
        //ba-button/span[starts-with(.,"Log in")]
        //a[contains(@href,"/account/logout")]
        //a[contains(@href,"/nx/b/account/")]
        try {
            $inputOrLogout = $tab->evaluate($xpath = '
            //form[@id = "execLoginrForm"] 
            | //main/section//form[@method="POST"]
            | //h1[contains(text(),"You are already logged in with a different account")]
            | //p[contains(text(), "Your membership number")]
            | //p[contains(text(), "Membership number:")]/following-sibling::p
            | //span[@id="membershipNumberValue"]
            | //h3[contains(text(),"Log in to your British Airways account")]
            | //h3[contains(text(),"Login to your British Airways account")]
            | //ba-button[contains(text(), "Find Flights")]
            | //button[@data-testid="login-button"]
            | //a[contains(@href,"/account/logout")]',
                EvaluateOptions::new()->visible(false));
            $tab->logPageState();
            /*if ($inputOrLogout->getNodeName() == 'BUTTON') {
                $inputOrLogout->click();
                return false;
            }*/
            $innerText = $inputOrLogout->getInnerText();
        } catch (\Exception $e) {
            $this->notificationSender->sendNotification("isLoggedIn: {$e->getMessage()} // MI");
            return false;
        }

        if (
            stristr($innerText, 'You are already logged')
            || stristr($innerText, 'Log out')
            || stristr($inputOrLogout->getAttribute('class'), 'logOut')
            || stristr($inputOrLogout->getAttribute('href'), '/account/logout')
            || in_array(strtoupper($inputOrLogout->getNodeName()), ['P', 'SPAN'])    
        ) {
            if (!$this->gerMe($tab)) {
                $this->notificationSender->sendNotification('refs #25044 british - need to check getCustomers. Potentially has a problems with IsLoggedIn  // IZ');
                $this->logout($tab);
                $tab->gotoUrl($this->countryUrl);

                return false;
            }

            return true;
        }

        $getUrl = $tab->getUrl();
        if (stristr($getUrl, 'gbr/account/pre-login?returnTo=')) {
            return false;
        }

        if (stristr($innerText, 'Find Flights')
            // https://www.britishairways.com/nx/b/en/gb/
            || $this->findPreg('#^https://www.britishairways.com/nx/b/[a-z]{2}/[a-z]{2}/$#', $getUrl)) {
            $tab->gotoUrl($this->countryUrl);
            if (stristr($tab->getUrl(), '/your-account?tab=your-trips')) {
                $inputOrLogout = $tab->evaluate($xpath, EvaluateOptions::new()->visible(false));
                if ($inputOrLogout->getNodeName() == 'BUTTON') {
                    $inputOrLogout->click();
                    return false;
                }
                return true;
            }
            return false;
        }
        return false;
    }

    public function getLoginId(Tab $tab): string
    {
        $number = $tab->findText('
            //p[contains(text(), "Your membership number")]
            | //p[contains(text(), "Membership number:")]/following-sibling::p
            | //span[@data-testid="membership-number"]
            | //span[@id="membershipNumberValue"]
        ', FindTextOptions::new()->preg('/(\d+)$/'));
        $this->logger->debug("number: $number");

        return $number;
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        //a[contains(@href,"/travel/loginr/execclub/_gf")]
        $logout = $tab->evaluate('//ba-header', EvaluateOptions::new()->allowNull(true)->timeout(10));
        if ($logout) {
            $logout->shadowRoot()->querySelector('a#logoutLinkDesktop',
                QuerySelectorOptions::new()->visible(false))->click();
        } else {
            $logout = $tab->evaluate('
                //a[contains(@href,"/travel/loginr/execclub/_gf")]
                | //button[contains(@data-testid, "log-button") and contains(@data-testid, "button") and contains(text(), "Log out")]
            ', EvaluateOptions::new()->allowNull(true)->visible(false)->timeout(10));
            if ($logout) {
                $logout->click();
            }
        }

        // Sometimes the redirect happens to the authorization form and sometimes to the main one
        $result = $tab->evaluate('//form[@id="execLoginrForm"] 
        | //app-searchbar | //form//input[@id="username"] 
        | //button[@id="log-button" and contains(text(),"Log in")]
        | //h2[@aria-label="GREAT DEALS TO LONDON."]',
            EvaluateOptions::new()->timeout(90));
        $tab->logPageState();
        $this->logger->notice("[Current URL]: {$tab->getUrl()}");

        if (strtoupper($result->getNodeName()) != 'APP-SEARCHBAR') {
            $tab->gotoUrl('https://www.britishairways.com/');
            
            $tab->evaluate('//app-searchbar | //button[@id="log-button"]', EvaluateOptions::new()->visible(false)->timeout(60));
            $tab->logPageState();
        }
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        sleep(1);
        $this->acceptAll($tab);

        if (stristr($tab->getUrl(), '/account/pre-login?returnTo=')) {
            $tab->evaluate('//button[@data-testid="login-button"]')->click();
        }

        $login = $tab->evaluate('//input[@id="username"] | //input[@id="membershipNumber"] 
            | //h1[contains(text(),"Verify Your Identity") or contains(text(),"Verify your identity")]', EvaluateOptions::new()->timeout(90));
        if (stristr(mb_strtolower($login->getInnerText()), 'verify your identity')) {
            return $this->identifyComputer($tab);
        }
        $login->setValue($credentials->getLogin());
        //$tab->saveScreenshot();
        // Element not found in cache. Possible last querySelector calls returned too much results, it was replaced
        try {
            $password = $tab->evaluate('//input[@id="password"] | //input[@id="input_password"]');
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $password = $tab->evaluate('//input[@id="password"] | //input[@id="input_password"] | //span[@data-testid="membership-number"]');
            if ($password->getNodeName() == 'SPAN') {
                return new LoginResult(true);
            }
        }

        $password->setValue($credentials->getPassword());
        if ($this->context->isServerCheck()) {
            $frame = $tab->selectFrameContainingSelector('//div[@id="checkbox"]',
                SelectFrameOptions::new()->method("evaluate")->visible(true));
            $frame->evaluate('//div[@id="checkbox"]')->click();
            // wait for green mark
            $frame->evaluate('//img[@alt="Check mark"]');
            // show
            /*$frame = $tab->selectFrameContainingSelector('//div[@class="interface-wrapper"]',
                SelectFrameOptions::new()->method("evaluate")->visible(true)->timeout(10)->allowNull(true));
            if ($frame->evaluate('//div[@class="interface-wrapper"]', EvaluateOptions::new()->timeout(0)->allowNull(true))) {
                $tab->saveScreenshot();
                $captcha = $this->parseCaptcha($tab);
                if ($captcha !== false) {
                    $tab->querySelector("iframe[data-hcaptcha-response]")->setProperty('data-hcaptcha-response',
                        $captcha);
                    $tab->querySelector('[name="g-recaptcha-response"]',
                        QuerySelectorOptions::new()->visible(false))->setValue($captcha);
                    $tab->querySelector('[name="h-captcha-response"]',
                        QuerySelectorOptions::new()->visible(false))->setValue($captcha);
                    $tab->querySelector('input[name="captcha"]',
                        QuerySelectorOptions::new()->visible(false))->setValue($captcha);
                }
            } else {
                $this->notificationSender->sendNotification('not show captcha wrapper // MI');
            }*/
            $tab->saveScreenshot();
            $tab->evaluate('//button[contains(text(),"Continue")] | //button[@id="ecuserlogbutton"]')->click();
        } else {
            $captcha = $tab->evaluate('//div[@data-captcha-sitekey]',
                EvaluateOptions::new()->allowNull(true)->timeout(5));
            if ($captcha) {
                $tab->showMessage('In order to log in into this account, you need to solve the CAPTCHA below and click the "Continue" button. Once logged in, sit back and relax, we will do the rest.', MessageType::WARNING);
                /*$frame = $tab->selectFrameContainingSelector('//div[@id="checkbox"]',
                    SelectFrameOptions::new()->method("evaluate")->visible(true));
                $frame->evaluate('//div[@id="checkbox"]')->click();*/

            } else {
                $tab->evaluate('//button[contains(text(),"Continue")] | //button[@id="ecuserlogbutton"]')->click();
            }

        }

        sleep(3);
        $this->ensAcceptAll($tab);
        $errorOrLogout = $tab->evaluate('
            //span[@id="error-element-captcha" or @id="error-element-password"]
            | //div[@id="prompt-alert"]
            | //p[contains(text(), "Two-factor authentication is an extra layer of security that ")]

            | //p[contains(text(), "Your membership number")]
            | //p[contains(text(), "Membership number:")]/following-sibling::p
            | //span[@id="membershipNumberValue"]
            | //span[@data-testid="membership-number"]

            | //h1[contains(text(),"Verify Your Identity") or contains(text(),"Verify your identity")]
            | //h1[contains(text(),"Sorry, we couldn\'t log you in")]
            | //p[contains(text(), "We are experiencing high demand on ba.com at the moment.")]
            | //p[contains(text(), "t sign you in at the moment. Please review your login details. If issue persists, your account may be locked. To unlock it, check your email")]
            ',
            EvaluateOptions::new()->timeout(120)->allowNull(true)->visible(false));
        $tab->showMessageSuccess();
        $this->ensAcceptAll($tab);
        $tab->clearMessage();
        $innerText = $errorOrLogout ? $errorOrLogout->getInnerText() ?? null : null;
        //$tab->saveScreenshot();
        // Login success
        if (isset($errorOrLogout) && str_starts_with(mb_strtolower($innerText), 'verify your identity')) {
            if ($this->context->isServerCheck()) {
                $question = $this->getQuestion($tab);
                if ($question) {
                    return LoginResult::question($question);
                }

                // TODO
                throw new \CheckRetryNeededException();
            }
            return $this->identifyComputer($tab);
        } elseif (isset($errorOrLogout) && str_starts_with($errorOrLogout->getAttribute('id'), 'error-element-captcha')) {
            return LoginResult::providerError($innerText);
        } elseif (!isset($errorOrLogout) && $tab->evaluate('//div[@id="ulp-hcaptcha"]/iframe',
                EvaluateOptions::new()->allowNull(true)->timeout(0))) {
            return LoginResult::captchaNotSolved();
        }  elseif (isset($errorOrLogout) && str_starts_with($errorOrLogout->getAttribute('id'), 'error-element-')) {
            return LoginResult::invalidPassword($innerText);
        } elseif (isset($innerText) && stristr($innerText,
                'Two-factor authentication is an extra layer of security that ')) {
            if ($btn = $tab->evaluate('//button[contains(text(),"Remind me later")]',
                EvaluateOptions::new()->timeout(0)->allowNull(true))) {
                $btn->click();
                sleep(3);
                return new LoginResult(true);
            }
            throw new ProfileUpdateException();

        } elseif (isset($errorOrLogout) && $errorOrLogout->getAttribute('id') === 'prompt-alert'
            && strpos($errorOrLogout->getInnerText(), 'temporarily blocked') !== false) {
            return   LoginResult::lockout($innerText);
        }  // Sorry, we couldn't log you in
        elseif (isset($innerText) && str_starts_with(mb_strtoupper($innerText),
                'Sorry, we couldn\'t log you in')) {
            return LoginResult::providerError($innerText);
        }  // We couldn't sign you in at the moment. Please review your login details. If issue persists, your account may be locked. To unlock it, check your email
        elseif (isset($innerText) && str_starts_with(mb_strtoupper($innerText),
                't sign you in at the moment. Please review your login details. If issue persists, your account may be locked')) {
            return LoginResult::lockout($innerText);
        } elseif (isset($innerText) && str_starts_with(mb_strtoupper($innerText),
                'We are experiencing high demand on ba.com at the moment.')) {
            $tab->gotoUrl('https://www.britishairways.com/travel/myaccount/');


            return new LoginResult(true);
        } elseif (isset($errorOrLogout) && in_array($errorOrLogout->getNodeName(), ['SPAN', 'P'])) {
            return new LoginResult(true);
        }

        return new LoginResult(false);
    }

    private function identifyComputer(Tab $tab)
    {
        $tab->showMessage('To continue updating this account, please enter your one-time code and click "Continue". Once logged in, sit back and relax; we will do the rest.', MessageType::WARNING);

        try {
            $errorOrLogout = $tab->findText('
                        //p[contains(text(), "Your membership number")]
                        | //p[contains(text(), "Membership number:")]/following-sibling::p
                        | //span[@id="membershipNumberValue"]
                        | //span[@data-testid="membership-number"]',
                FindTextOptions::new()->timeout(180)->allowNull(true)->visible(false));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $errorOrLogout = $tab->findText('
                        //p[contains(text(), "Your membership number")]
                        | //p[contains(text(), "Membership number:")]/following-sibling::p
                        | //span[@id="membershipNumberValue"]
                        | //span[@data-testid="membership-number"]',
                FindTextOptions::new()->timeout(180)->allowNull(true)->visible(false));
        }

        if (!$errorOrLogout) {
            return LoginResult::identifyComputer();
        } else {
            $this->acceptAll($tab);

            return new LoginResult(true);
        }
    }

    private function ensAcceptAll(Tab $tab)
    {
        $acceptAll = $tab->evaluate('//button[@id="ensAcceptAll"]',
            EvaluateOptions::new()->allowNull(true)->timeout(0));
        if ($acceptAll) {
            $acceptAll->click();
        }
    }

    private function parseCaptcha(Tab $tab)
    {
        $this->logger->notice(__METHOD__);
        $key = $tab->evaluate('//div[@data-captcha-sitekey]', EvaluateOptions::new()->timeout(5)->allowNull(true));

        if (!$key) {
            return false;
        }

        $parameters = [
            "method"  => "hcaptcha",
            "pageurl" => $tab->getUrl(),
            "domain"  => "js.hcaptcha.com",
        ];

        return $this->captchaServices->recognize($key->getAttribute('data-captcha-sitekey'), RucaptchaProvider::ID, $parameters);
    }

    /**
     * NOTICE: Do not touch, it may be useful in future
     * @param \AwardWallet\ExtensionWorker\Tab $tab
     * @param \AwardWallet\Schema\Parser\Common\Statement $statement
     * @param \AwardWallet\ExtensionWorker\AccountOptions $accountOptions
     * @return void
     */
    private function parseTransactionsOld(Tab $tab, Statement $statement, AccountOptions $accountOptions)
    {
        $this->logger->info('Expiration date', ['Header' => 3]);
        $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/viewtransaction/execclub/_gf/%s?eId=106012&prim=execcl', $accountOptions));
        

        $tab->evaluate("(//div[@class='info-detail-main-transaction-row'])[1]", EvaluateOptions::new()->allowNull(true)->timeout(15));
        $exp = $tab->evaluateAll("//div[@class='info-detail-main-transaction-row']");
        $this->logger->debug("Total transactions found: " . count($exp));

        foreach ($exp as $row) {
            // Description
            $activity = $tab->findText("div[starts-with(@id,'resultRow')][3]/p",
                FindTextOptions::new()->contextNode($row));
            // refs #7665 - ignore certain activities
            if (!$this->ignoreActivity($activity)) {
                $date = str_replace('Transaction:', '', $tab->findText("div[starts-with(@id,'resultRow')][1]/p[1]",
                    FindTextOptions::new()->contextNode($row)));
                // refs #9168 - ignore row with empty avios
                $avios = $tab->findText("div[starts-with(@id,'resultRow')][5]/p[2]",
                    FindTextOptions::new()->contextNode($row));
                $this->logger->debug("Date $date / $avios");

                // refs #7665 - ignore certain activities, part 2
                $reference = $this->findPreg("/Reference:\s*([^\s]+)/ims", $activity);

                if (strpos($activity, "Avios refund") !== false || isset($ignoreBookings[$reference])) {
                    $this->logger->debug("Booking Reference: {$reference}");

                    if (isset($ignoreBookings[$reference])) {
                        if ($ignoreBookings[$reference] == -floatval($avios)) {
                            $this->logger->notice("Skip Avios refund: {$reference}");

                            continue;
                        } else {
                            $this->logger->notice("First transaction not found: {$reference}");
                        }
                    } else {
                        $this->logger->notice("Add Avios refund to ignore transactions: {$reference}");
                        $ignoreBookings[$reference] = $avios;

                        continue;
                    }
                }

                if ($avios != '' && $avios != '-') {
                    $exp = strtotime($date);
                    $statement->addProperty('LastActivity', $exp);

                    if ($exp) {
                        $statement->setExpirationDate(strtotime("+3 year", $exp));
                    }

                    break;
                }
            }
        }
    }

    private function parseTransactions(Tab $tab, Statement $statement, AccountOptions $accountOptions)
    {
        $this->logger->info('Expiration date', ['Header' => 3]);
        $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/viewtransaction/execclub/_gf/%s?eId=106012&prim=execcl', $accountOptions));
        
        $tab->evaluate('(//article[div[contains(@class, "personaldata")]])[1]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        $tab->logPageState();

        /*
        while(1) {
            $loadMore = $tab->evaluate('//button[@data-testid="load-more-button"]', EvaluateOptions::new()->allowNull(true)->timeout(5));
            if (!isset($loadMore)) {
                break;
            }
            $loadMore->click();
        }
        */

        $nodes = $tab->evaluateAll('//article[div[contains(@class, "personaldata")]]');
        $nodesCount = count($nodes);
        $this->logger->debug("Total transactions found: " . $nodesCount);

        foreach ($nodes as $node) {
            // Description
            $activity = $tab->findText('./div[1]/div[2]/p | ./div[1]/div[2]/div[1]/p', FindTextOptions::new()->contextNode($node));
            // refs #7665 - ignore certain activities
            if ($this->ignoreActivity($activity)) {
                continue;
            }
            $date = str_replace('Transaction:', '', $tab->findText('./div[1]/div[3]/p | ./div[1]/div[2]/div[2]/p', FindTextOptions::new()->contextNode($node)));
            // refs #9168 - ignore row with empty avios
            $avios = $tab->findText('./div[2]/div[1]/p[1]', FindTextOptions::new()->contextNode($node));
            $this->logger->debug("Date {$date} / {$avios}");

            // refs #7665 - ignore certain activities, part 2
            $reference = $this->findPreg("/Reference:\s*([^\s]+)/ims", $activity);

            if (strpos($activity, "Avios refund") !== false || isset($ignoreBookings[$reference])) {
                $this->logger->debug("Booking Reference: {$reference}");

                if (isset($ignoreBookings[$reference])) {
                    if ($ignoreBookings[$reference] == -floatval($avios)) {
                        $this->logger->notice("Skip Avios refund: {$reference}");

                        continue;
                    } else {
                        $this->logger->notice("First transaction not found: {$reference}");
                    }
                } else {
                    $this->logger->notice("Add Avios refund to ignore transactions: {$reference}");
                    $ignoreBookings[$reference] = $avios;

                    continue;
                }
            }

            if ($avios != '' && $avios != '-') {
                $exp = strtotime($this->text->modifyDateFormat($date, '/'));
                $statement->addProperty('LastActivity', $exp);

                if ($exp) {
                    $statement->setExpirationDate(strtotime("+3 year", $exp));
                } else {
                    continue;
                }

                break;
            }
        }
    }

    /**
     * NOTICE: Do not touch, it may be useful in future
     * @param \AwardWallet\ExtensionWorker\Tab $tab
     * @param \AwardWallet\Schema\Parser\Common\Statement $statement
     * @param \AwardWallet\ExtensionWorker\AccountOptions $accountOptions
     * @return void
     */
    private function parseEvouchersOld(Tab $tab, Statement $statement, AccountOptions $accountOptions)
    {
        $this->logger->info('My eVouchers', ['Header' => 3]);
        $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/membership/execclub/_gf/%s?eId=188010',
            $accountOptions));
        
        if (stristr( $tab->getUrl(), '/travel/viewtransaction/execclub/_gf')) {
            $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/membership/execclub/_gf/%s?eId=188010',
                $accountOptions));
            
        }

        $tab->evaluate('//h2[contains(text(),"My eVouchers")] | //h3[contains(text(), "You have no vouchers")]', EvaluateOptions::new()->allowNull(true)->timeout(50));
        $vouchers = $tab->evaluateAll("//div[@id = 'unusedVouchers']/div[@class='table-body']");
        $this->logger->debug("Total vouchers found: " . count($vouchers));

        foreach ($vouchers as $row) {
            $code = $tab->findText("p[contains(@class,'voucher-list-number')]/span[not(contains(text(), 'number'))]",
                FindTextOptions::new()->contextNode($row));
            //# Type
            $displayName = $tab->findText("p[contains(@class,'voucher-list-type')]",
                FindTextOptions::new()->contextNode($row));
            //# Expiry
            $exp = $tab->findText("p[contains(@class,'voucher-list-details') and span[normalize-space(text())='Expiry']]/span[@class='text']",
                FindTextOptions::new()->contextNode($row));

            if (strtotime($exp) && isset($displayName, $code)) {
                $statement->addSubAccount([
                    'Code'           => 'britishVouchers' . $code,
                    'DisplayName'    => "Voucher #$code - $displayName",
                    'Balance'        => null,
                    'ExpirationDate' => strtotime($exp),
                ]);
            }
        }
    }

    private function parseEvouchers(Tab $tab, Statement $statement, AccountOptions $accountOptions)
    {
        $this->logger->info('My eVouchers', ['Header' => 3]);
        $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/membership/execclub/_gf/%s?eId=188010', $accountOptions));
        

        if (stristr( $tab->getUrl(), '/travel/viewtransaction/execclub/_gf')) {
            $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/membership/execclub/_gf/%s?eId=188010', $accountOptions));
            
        }
        /*
        $el = $tab->evaluate('//span[contains(text(), "Active vouchers")] | //h3[contains(text(), "You have no vouchers")]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        $tab->logPageState();
        if (
            !isset($el) || 
            (isset($el) && $el->getNodeName() == 'SPAN')
        ) {
            $this->notificationSender->sendNotification('refs #25044 - need to check evouchers // IZ');
        }

        $elementsOnCurrentPage = $tab->findText('//p[@id="text-custom" and contains(text(), "Showing")]', FindTextOptions::new()->preg('/Showing\s*(\d+)/')->allowNull(True)->timeout(10));
        $allElemenets = $tab->findText('//p[@id="text-custom" and contains(text(), "Showing")]', FindTextOptions::new()->preg('/(\d+)\s*vouchers/')->allowNull(True));
        if ($elementsOnCurrentPage !== $allElemenets) {
            $this->notificationSender->sendNotification('refs #25044 - need to check evouchers pagination // IZ');
        }
        */
        $tab->logPageState();
        $i = 1;
        while ($i <= 30) {
            $i++;
            $loadMore = $tab->evaluate('//button[@data-testid="load-more-button" or contains(text(), "Load more")]', EvaluateOptions::new()->allowNull(true)->timeout(5));
            if (!isset($loadMore)) {
                break;
            }
            $loadMore->click();
            $this->watchdogControl->increaseTimeLimit(60);
        }
        $tab->logPageState();
        $vouchers = $tab->evaluateAll('//article[contains(@id, "vouchers-card")]');
        $this->logger->debug("Total vouchers found: " . count($vouchers));

        foreach ($vouchers as $voucher) {
            $code = $tab->findText('./div[2]/div[1]/div[1]/p[2]', FindTextOptions::new()->contextNode($voucher)->allowNull(True));
            //# Type
            $displayName = $tab->findText('./div/p', FindTextOptions::new()->contextNode($voucher)->allowNull(True));
            //# Expiry
            $exp = $tab->findText('./div[2]/div[1]/div[3]/p[2]', FindTextOptions::new()->contextNode($voucher)->allowNull(True));

            if (!isset($code, $displayName) || !strtotime($exp)) {
                $tab->logPageState();
                continue;
            }

            if (strtotime($exp) && isset($displayName, $code)) {
                $statement->addSubAccount([
                    'Code'           => 'britishVouchers' . $code,
                    'DisplayName'    => "Voucher #$code - $displayName",
                    'Balance'        => null,
                    'ExpirationDate' => strtotime($exp),
                ]);
            }
        }
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        /*if ($this->context->isServerCheck()) {
            $this->notificationSender->sendNotification('success server login // MI');
        }*/
        $tab->logPageState();
        //$tab->evaluate('//ba-button[contains(@href,"/travel/echome/execclub/_gf/")]')->click();
        $me = $this->gerMe($tab);
        if (!isset($me)) {
            throw new CheckException('The website is experiencing technical difficulties, please try to check your balance at a later time.', ACCOUNT_PROVIDER_ERROR);
        }
        $st = $master->createStatement();
        $st->setNumber($me->membershipId);
        $st->addProperty('Name', beautifulName("$me->givenName $me->familyName"));
        $account = $this->getAccount($tab, $me->membershipId);

        $st
             // Balance - Avios
            ->setBalance($account->avios->userPoints)
            // Tier Point collection year ends
            ->addProperty('YearEnds', strtotime($account->commonTier->pointsEndDate))
            // Tier Points
            ->addProperty('TierPoints', $account->commonTier->totalPoints)
            // My Lifetime Tier Points
            ->addProperty('LifetimeTierPoints', $account->lifetimeTier->points)
            // Eligible Flights
            ->addProperty('EligibleFlightsToNextTier', $account->commonTier->flights);
            // Date of joining the club
            // ->addProperty('DateOfJoining', $detail->lifeTimeTierPoints->balance);
        ;
        // Membership card expiry:
        if (isset($account->commonTier->cardExpiryDate)) {
            $st->addProperty('CardExpiryDate', strtotime($account->commonTier->cardExpiryDate));
        }

        if ($account->commonTier->flights > 25) {
            $this->notificationSender->sendNotification('Eligible Flights // MI');
        }


        // Date of joining the club
        switch ($account->commonTier->id) {
            case 'BLUE':
            case 'EXECUTIVE_BLUE':
                $st->addProperty('Level', 'Blue Member');

                break;
        }        

        /*
        $this->parseTransactions($tab, $st, $accountOptions);
        */
        $this->parseEvouchers($tab, $st, $accountOptions);
    }

    private function fetchLastName(Tab $tab)
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl('https://www.britishairways.com/nx/b/account/en/usa/account/user');
        
        $el = $tab->evaluate('
            //span[@id="text-custom" and contains(text(), "Name")]/following-sibling::span
            | //input[@id="code"]
            | //h1[contains(text(), "Use fingerprint or face recognition to login")]
            | //h1[contains(text(), "Log In Faster on This Device")]
            | //*[contains(text(), "Log in to your British Airways account")]
        ', EvaluateOptions::new()->allowNull(true)->timeout(10)->allowNull(True));
        $tab->logPageState();
        if (!isset($el)) {
            $this->logger->debug('error while fetching lastname');
            /*
            $this->notificationSender->sendNotification('refs #25044 british - error while fetching lastname // IZ');
            */
            return;
        }

        if (
            // login form
            strstr($el->getInnerText(), "Log in to your British Airways account")
        ) {
            $this->watchdogControl->increaseTimeLimit(180);
            $tab->showMessage('The website requires you to log in. Please enter your credentials in the login form. After successful login, you will be redirected to the target page, and we will continue updating your account.');
            $lastName = $tab->findText('//span[@id="text-custom" and contains(text(), "Name")]/following-sibling::span', FindTextOptions::new()->timeout(180)->allowNull(true)->visible(false)->nonEmptyString()->preg('/\s(.*)/'));
        } else if (
            // 2fa and input field with one time code
            $el->getNodeName() == 'INPUT'
        ) {
            $this->watchdogControl->increaseTimeLimit(180);
            $tab->showMessage('To continue updating this account, please enter your one-time code and click "Continue". Once logged in, sit back and relax; we will do the rest.');
            $lastName = $tab->findText('//span[@id="text-custom" and contains(text(), "Name")]/following-sibling::span', FindTextOptions::new()->timeout(180)->allowNull(true)->visible(false)->nonEmptyString()->preg('/\s(.*)/'));
        } else if (
            // 2fa with fingerprint or face recognition
            strstr($el->getInnerText(), "Use fingerprint or face recognition to login")
            || strstr($el->getInnerText(), "Log In Faster on This Device")
        ) {
            $this->watchdogControl->increaseTimeLimit(180);
            $tab->showMessage('Two-factor authentication is required by the website. Please follow the website\'s instructions to complete the login verification. After successful authentication, we will continue updating your account.');
            $lastName = $tab->findText('//span[@id="text-custom" and contains(text(), "Name")]/following-sibling::span', FindTextOptions::new()->timeout(180)->allowNull(true)->visible(false)->nonEmptyString()->preg('/\s(.*)/'));
        } else {
            // direct access to profile page
            $lastName = $tab->findText('//span[@id="text-custom" and contains(text(), "Name")]/following-sibling::span', FindTextOptions::new()->timeout(30)->allowNull(true)->visible(false)->nonEmptyString()->preg('/\s(.*)/'));
        }

        $tab->logPageState();
        /*
        if (!isset($lastName)) {
            $this->notificationSender->sendNotification('refs #25044 - lastname not found // IZ');
            return;
        }

        $this->notificationSender->sendNotification('refs #25044 - lastname found // IZ');
        */
        return $lastName;
    }

    public function parseItineraries(
        Tab $tab,
        Master $master,
        AccountOptions $options,
        ParseItinerariesOptions $parseItinerariesOptions
    ): void {
        $this->logger->notice("[Current URL]: {$tab->getUrl()}");
        try {
            if (!empty($options->login3)) {
                $lastName = $options->login3;
            } else {
                $tab->gotoUrl('https://www.britishairways.com/nx/b/customerhub/en/usa/your-account?tab=your-trips');
                if ($name = $tab->findTextNullable('//input[@name="lastName"]/@value',
                    FindTextOptions::new()->visible(false))) {
                    $lastName = $name;
                    //$this->notificationSender->sendNotification('success last name // MI');
                } else {
                    //$this->notificationSender->sendNotification('not last name // MI');
                    //$lastName = $this->fetchLastName($tab);
                }
            }

            if (!isset($lastName)) {
                $this->logger->error('parse itineraries will be skipped cause could not fetch last name');
                return;
            };
            $this->logger->notice("Last Name: $lastName");
            $this->logger->notice('parse past itineraries: ' . ($parseItinerariesOptions->isParsePastItineraries() ? 'true' : 'false'));

            $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/viewaccount/execclub/_gf/%s?eId=106010',
                $options));

            $tab->evaluate('
            //h3[contains(text(),"We can\'t find any bookings for this account")]
            | //a[contains(@class, "small-btn") and span[contains(text(), "Manage My Booking")]]
            | //div[@data-testid="upcoming-trips"]//p[contains(text(), "You have no upcoming trips")]
            | //button[contains(text(), "Manage your trip")]',
                EvaluateOptions::new()->visible(false)->allowNull(true)->timeout(10));

            $tab->logPageState();
            if (
                $tab->findTextNullable('
                //h3[contains(text(),"We can\'t find any bookings for this account")]
                | //div[@data-testid="upcoming-trips"]//p[contains(text(), "You have no upcoming trips")]
                ') && !$parseItinerariesOptions->isParsePastItineraries()
            ) {
                $master->setNoItineraries(true);

                return;
            }

            /*
            $elementsOnCurrentPage = $tab->findText('//p[@id="text-custom" and contains(text(), "Showing")]', FindTextOptions::new()->preg('/Showing\s*(\d+)/')->allowNull(True));
            $allElemenets = $tab->findText('//p[@id="text-custom" and contains(text(), "Showing")]', FindTextOptions::new()->preg('/of\s(\d+)/')->allowNull(True));
            if (
                isset($elementsOnCurrentPage, $allElemenets)
            ) {
                if ($elementsOnCurrentPage !== $allElemenets) {
                    $this->notificationSender->sendNotification('refs #25044 - need to check future itineraries (pagination issue) // IZ');
                } else {
                    $this->notificationSender->sendNotification('refs #25044 - need to check future itineraries // IZ');
                }
            }
            */

            $allBookingsButton = $tab->evaluate('
            //a[span[contains(text(), "View all bookings") or contains(text(), "View all current flight bookings")]]/@href
            | //button[@data-testid="view-all-trips-button"]',
                EvaluateOptions::new()->allowNull(true));

            if (isset($allBookingsButton)) {
                $this->logger->notice(">>> Get page with all bookings");
                $allBookingsButton->click();
            }

            /*
            $noPastItineraries = $tab->evaluate('//p[contains(text(), "You have no past trips within the last 12 months.")]', EvaluateOptions::new()->allowNull(true)->timeout(10));
            $tab->logPageState();
            if (!isset($noPastItineraries)) {
                $this->notificationSender->sendNotification('refs #25044 - need to check past itineraries // IZ');
            }
            */

            if ($error = $tab->findTextNullable("//h1[text()='Sorry']/following-sibling::p[1][starts-with(normalize-space(),'Sorry, there seems to be a technical problem')]")) {
                $this->logger->error($error);

                return;
            }

            if ($error = $tab->findTextNullable("//h1[text()='Sorry']/following-sibling::p[1][starts-with(normalize-space(),'We regret to advise that this section of the site is temporarily unavailable')]")) {
                $this->logger->error($error);

                return;
            }

            /*
            if ($tab->evaluate('//p[@id="text-custom" and contains(text(), "past trips")]/following-sibling::div/button', EvaluateOptions::new()->allowNull(true))) {
                $this->notificationSender->sendNotification('refs #25044 - need to check past itineraries pagination // IZ');
            }

            if ($tab->evaluate('//p[@id="text-custom" and contains(text(), "upcoming trips")]/following-sibling::div/button', EvaluateOptions::new()->allowNull(true))) {
                $this->notificationSender->sendNotification('refs #25044 - need to check future itineraries pagination // IZ');
            }
            */

            $tab->logPageState();
            $this->logger->debug('loading all itineraries');
            $i = 1;
            while ($i <= 10) {
                $i++;
                $loadMorePastItineraries = $tab->evaluate('//p[@id="text-custom" and contains(text(), "past trips")]/following-sibling::div/button',
                    EvaluateOptions::new()->allowNull(true)->timeout(3));
                if (isset($loadMorePastItineraries)) {
                    $loadMorePastItineraries->click();
                    $this->watchdogControl->increaseTimeLimit(60);
                }
                $loadMoreFutureItineraries = $tab->evaluate('//p[@id="text-custom" and contains(text(), "upcoming trips")]/following-sibling::div/button',
                    EvaluateOptions::new()->allowNull(true)->timeout(3));
                if (isset($loadMoreFutureItineraries)) {
                    $loadMoreFutureItineraries->click();
                    $this->watchdogControl->increaseTimeLimit(60);
                }
                if (
                    !isset($loadMoreFutureItineraries)
                    && !isset($loadMorePastItineraries)
                ) {
                    break;
                }
            }

            $tab->logPageState();
            $upcomingItinerariesConfirmations = $tab->findTextAll('//div[@data-testid="upcoming-trips"]//p[@id="text-custom" and contains(text(), "Booking reference")]',
                FindTextOptions::new()->preg('/Booking\sreference\s(.*)/'));
            $pastItinerariesConfirmations = $tab->findTextAll('//section[@data-testid="past-trips"]//p[@id="text-custom" and contains(text(), "Booking reference")]',
                FindTextOptions::new()->preg('/Booking\sreference\s(.*)/'));
            $confirmations = array_merge($upcomingItinerariesConfirmations);
            if ($parseItinerariesOptions->isParsePastItineraries()) {
                $confirmations = array_merge($confirmations, $pastItinerariesConfirmations);
            }

            $this->logger->debug('Found ' . count($confirmations) . ' confirmations');
            if (count($confirmations) == 0) {
                $this->logger->debug('no itineraries found');
                return;
            }

            foreach ($confirmations as $confirmation) {
                $confNoFields = [
                    'ConfNo' => $confirmation,
                    'LastName' => $lastName
                ];
                $this->logger->info("ConfNo: {$confirmation}, LastName: {$lastName}", ['Header' => 3]);
                $confNoOptions = new ConfNoOptions(false);
                $tab->gotoUrl($this->getLoginWithConfNoStartingUrl($confNoFields, $confNoOptions));

                $this->watchdogControl->increaseTimeLimit(60);
                $loginWithConfNoResult = $this->loginWithConfNo($tab, $confNoFields, $confNoOptions);
                if (stristr($loginWithConfNoResult->errorMessage, 'Error Sorry, we are unable to display your booking as all the flights have been flown.')) {
                    // 25044#note-67
                    // Error Access to this booking has now been prohibited due to too many unsuccessful attempts. Please check your travel details are correct and retry in 24 hours.
                    $this->logger->error($loginWithConfNoResult->errorMessage);
                    break;
                }
                if (!$loginWithConfNoResult->isSuccess()) {
                    continue;
                }
                $this->watchdogControl->increaseTimeLimit(120);
                $this->retrieveByConfNo($tab, $master, $confNoFields, $confNoOptions);
                /*
                $this->parseItinerary2025OldDesign($tab, $master);
                */
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }


    public function parseItinerariesOld(
        Tab $tab,
        Master $master,
        AccountOptions $options,
        ParseItinerariesOptions $parseItinerariesOptions
    ): void {
        try {
            $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/viewaccount/execclub/_gf/%s?eId=106010', $options));
            
            $tab->evaluate('
                //h3[contains(text(),"We can\'t find any bookings for this account")]
                | //a[contains(@class, "small-btn") and span[contains(text(), "Manage My Booking")]]
                | //div[@data-testid="upcoming-trips"]//p[contains(text(), "You have no upcoming trips")]
                | //button[contains(text(), "Manage your trip")]
            ', EvaluateOptions::new()->visible(false)->allowNull(true)->timeout(10));
            $tab->logPageState();
            if (
                $tab->findTextNullable('
                    //h3[contains(text(),"We can\'t find any bookings for this account")]
                    | //div[@data-testid="upcoming-trips"]//p[contains(text(), "You have no upcoming trips")]
                ')
            ) {
                if ($parseItinerariesOptions->isParsePastItineraries()) {
                    //$this->parsePastItineraries();

                    if (count($master->getItineraries()) > 0) {
                        return;
                    }
                }// if ($this->ParsePastIts)
                $master->setNoItineraries(true);

                return;
            }

            $this->notificationSender->sendNotification('refs #25044 - need to check future itineraries // IZ');
            // View all bookings
            //a[span[contains(text(), 'View all bookings')]]/@href
            $allBookingsButton = $tab->evaluate('
                //a[span[contains(text(), "View all bookings") or contains(text(), "View all current flight bookings")]]/@href
                | //button[@data-testid="view-all-trips-button"]
            ', EvaluateOptions::new()->allowNull(true));

            if (isset($allBookingsButton)) {
                $this->logger->notice(">>> Get page with all bookings");
                $allBookingsButton->click();
            }

            $noPastItineraries = $tab->evaluate('//p[contains(text(), "You have no past trips within the last 12 months.")]', EvaluateOptions::new()->allowNull(true)->timeout(10));
            $tab->logPageState();
            if (!isset($noPastItineraries)) {
                $this->notificationSender->sendNotification('refs #25044 - need to check past itineraries // IZ');                
            }
 
            /*
            if ($allBookingsUrl = $tab->findTextNullable("//a[span[contains(text(), 'View all bookings') or contains(text(), 'View all current flight bookings')]]/@href")) {
                $this->logger->notice(">>> Get page with all bookings");
                $tab->gotoUrl($allBookingsUrl);
                $tab->evaluate("//section[@id='idContentUpcomingFlights']");
            }
            */

            if ($error = $tab->findTextNullable("//h1[text()='Sorry']/following-sibling::p[1][starts-with(normalize-space(),'Sorry, there seems to be a technical problem')]")) {
                $this->logger->error($error);

                return;
            }

            if ($error = $tab->findTextNullable("//h1[text()='Sorry']/following-sibling::p[1][starts-with(normalize-space(),'We regret to advise that this section of the site is temporarily unavailable')]")) {
                $this->logger->error($error);

                return;
            }



            $links = $tab->evaluateAll("//a[contains(@class, 'small-btn') and span[contains(text(), 'Manage My Booking')]]");
            $this->logger->notice(">>> Total " . count($links) . " reservations were found");
            $preParseCancelled = [];
            $pnrs = [];

            foreach ($links as $link) {
                $url = $link->getAttribute('href');
                $pnr = $tab->findText("./ancestor::div[count(./descendant::text()[normalize-space()='Booking Reference'])=1][1]/descendant::text()[normalize-space()='Booking Reference']/following::text()[normalize-space()!=''][1]",
                    FindTextOptions::new()->contextNode($link)->preg("/^[A-Z\d]{5,}$/")->timeout(1));
                $pnrs[$url] = $pnr;
            }
            $this->logger->debug("[pnrs]: " . var_export($pnrs, true), ['pre' => true]);

            foreach ($pnrs as $url => $pnr) {
                $this->logger->debug("[PNR]: " . $pnr);
                $this->logger->debug($url);

                try {
                    if ($this->findPreg("#https://www\.britishairways\.com/travel#", $url)) {
                        $this->logger->notice('Second reservation parsing variant');
                        $tab->gotoUrl($url);
                        
                        $oldOrNewDesign = $tab->evaluate('//h1[starts-with(normalize-space(),"Booking") and ./strong]
                        | //h2[@id="flight-change-title"]',
                            EvaluateOptions::new()->allowNull(true)->timeout(20));
                        if ($oldOrNewDesign && $oldOrNewDesign->getInnerText() == 'Flights') {
                            $this->logger->notice('V3 reservation parsing variant');
                            $this->notificationSender->sendNotification('V3 reservation // MI');
                            //$tab->saveScreenshot();
                            $this->parseItinerary2025FromDataLayer($tab, $master);
                            continue;
                        }
                        //$tab->saveScreenshot();

                        $errorOrDetail = $tab->findTextNullable('
                        //li[contains(.,"We\'re sorry, but ba.com is very busy at the moment, and couldn")]
                        | //li[contains(text(), "we are unable to display your booking")]
                        | //li[contains(text(), "Sorry, We are unable to find your booking.")]
                        | //h3[contains(text(), "Sorry, we can\'t display this booking")]
                        | //span[not(contains(@class, "wrapText")) and contains(text(), "There are no confirmed flights in this booking")]
                        | //li[contains(text(), "Sorry, we can\'t display this booking")]
                        | //li[contains(text(), "There was a problem with your request, please try again later.")]
                        | //h1[contains(text(), "Confirm your contact details")]',
                            FindTextOptions::new()->timeout(10));

                        if (!empty($errorOrDetail)) {
                            $this->logger->error($errorOrDetail);
                            if (stripos($errorOrDetail, 'Confirm your contact details') !== false) {
                                $this->notificationSender->sendNotification('v3 Confirm your contact details // MI');
                                continue;
                            }
                            if (stripos($errorOrDetail,
                                    'We\'re sorry, but ba.com is very busy at the moment, and couldn') !== false) {
                                continue;
                            }

                            if (isset($preParseCancelled[$pnr])) {
                                $this->logger->info("[{$this->itCount}] Parse Flight #{$pnr}", ['Header' => 3]);
                                $this->itCount++;
                                $r = $master->add()->flight();
                                $r->general()
                                    ->confirmation($pnr)
                                    ->status('Cancelled')
                                    ->cancelled();
                                $this->getSegmentFromPreParse($r, $preParseCancelled[$pnr]);
                            }

                            continue;
                        }
                        $nonFlightLink = $tab->findTextNullable('(//span[contains(text(), "Print non-flight voucher")])[1]/ancestor::a[1]/@href');

                        $msgCancelled =
                            $tab->findTextNullable("//h3[contains(@class, 'refund-progress')][contains(normalize-space(),'We are currently processing a cancellation and refund for this booking')]") ?:
                                $tab->findTextNullable("//span[contains(@class, 'wrapText') and normalize-space()='There are no confirmed flights in this booking']/following::text()[normalize-space()!=''][1][normalize-space()='There are no confirmed flights in this booking.']");

                        $cntBefore = count($master->getItineraries());

                        if ($msgCancelled) {
                            $this->logger->info("[{$this->itCount}] Parse Flight #{$pnr}", ['Header' => 3]);
                            $this->itCount++;

                            $this->logger->warning($msgCancelled);
                            $r = $master->add()->flight();
                            $r->general()
                                ->confirmation($pnr)
                                ->status('Cancelled')
                                ->cancelled();

                            if (isset($preParseCancelled[$pnr])) {
                                $this->getSegmentFromPreParse($r, $preParseCancelled[$pnr]);
                            }
                            $result = [];
                        } else {
                            $result = $this->parseItinerary($tab, $master, $pnr, $preParseCancelled);
                        }

                        if (!is_string($result) && (count($master->getItineraries()) - $cntBefore) > 0) {
                            $this->logger->debug('Reservation parsed');
                            $its = $master->getItineraries();
                            $itLast = end($its);

                            if ($nonFlightLink && !$itLast->getCancelled()) {
                                // TODO - not implemented
                                //$this->parseVouchers($nonFlightLink);
                            }
                        } elseif (isset($result) && is_string($result)) {
                            $this->logger->error($result);
                        } else {
                            $this->logger->error("something went wrong");
                        }
                        sleep(2);
                    }
                } catch (\Exception $e) {
                    $this->logger->error($e->getMessage());
                }
            }
            $this->logger->info('[ParseItineraries. date: ' . date('Y/m/d H:i:s') . ']');

            if ($parseItinerariesOptions->isParsePastItineraries()) {
                $this->parsePastItineraries($tab, $master, $options);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->notificationSender->sendNotification("error it // MI");
        }
    }

    private function parseItinerary2025FromDataLayer(
        Tab $tab,
        Master $master
    ) {
        $this->logger->notice(__METHOD__);

        $dataLayer = $this->findPreg('#console\.log\(window.dataLayer\)}}\)\((.+?)\);\s*</script>#', $tab->getHtml());
        //$this->logger->info($dataLayer);
        $dataLayer = json_decode($dataLayer);

        $f = $master->add()->flight();
        $f->general()->confirmation($dataLayer->PNR, 'Booking Confirmation');

        foreach($dataLayer->summary->items as $summaryItem) {
            foreach ($summaryItem as $item) {
                $f->general()->traveller($item->title);
            }
        }

        foreach ($dataLayer->flightDetailsSections as $flightDetailsSection) {
            foreach ($flightDetailsSection->flights as $flight) {
                $s = $f->addSegment();
                $s->airline()->name($flight->airlineCode);
                $s->airline()->number($flight->flightNumber);

                $s->departure()->code($flight->originAirportCode);
                $s->departure()->date2($flight->originDateTime);
                $s->departure()->terminal($flight->originTerminal->params->terminal->content);

                $s->arrival()->code($flight->destinationAirportCode);
                $s->arrival()->date2($flight->destinationDateTime);
                $s->arrival()->terminal($flight->destinationTerminal->params->terminal->content);

                $s->extra()->aircraft($flight->flightPlaneRef);

                $hours = $flight->flightDuration->params->hours->content;
                $minutes = $flight->flightDuration->params->minutes->content;
                $s->extra()->duration("$hours hour and $minutes minutes");

                $s->extra()->cabin($flight->flightClass);
            }
        }
    }

    private function parseItinerary2025OldDesign(
        Tab $tab,
        Master $master
    ) {
        $this->logger->notice(__METHOD__);
        $tab->startCache();
        try {
            $this->watchdogControl->increaseTimeLimit(120);
            $confirmation = $tab->findText('//strong[@class="personaldata"]');
            $tab->logPageState();
            $f = $master->add()->flight();
            $f->general()->confirmation($confirmation, 'Booking reference');

            $passengersDataElements = $tab->evaluateAll('//p[contains(@class, "passenger") and span]');
            foreach ($passengersDataElements as $passengerDataElement) {
                $firstName = $tab->findText('./span[1]', FindTextOptions::new()->contextNode($passengerDataElement)->allowNull(True)->nonEmptyString());
                $lastName = $tab->findText('./span[2]', FindTextOptions::new()->contextNode($passengerDataElement)->allowNull(True)->nonEmptyString());
                if (!isset($firstName, $lastName)) {
                    continue;
                }
                $f->general()->traveller("{$firstName} {$lastName}");
            }

            $segmentsDataElements = $tab->evaluateAll('//div[contains(@class,"flight-list")]/a[contains(@class, "flight-list__")]');
            $arrayFlight = [];
            foreach ($segmentsDataElements as $segmentsDataElement) {
                // General info
                $airlineCode = $this->findPreg('/Flight\s([A-z]+)\d+\s\|/', $segmentsDataElement->getAttribute('aria-label'));
                $flightNumber = $this->findPreg('/\d+/', $segmentsDataElement->getAttribute('data-modal'));
                // Departure info
                $departureAirportCode = $segmentsDataElement->getAttribute('data-departure-airport-code');
                $dataDepartureElement = $tab->evaluate('//div[contains(@data-modal-name, "flight-' . $flightNumber . '")]//div[p[span[contains(text(), "Depart")]]]', EvaluateOptions::new()->allowNull(true)->visible(false));
                if (isset($dataDepartureElement)) {
                    $departureTerminal = $tab->findText('.//span[contains(text(), "Terminal")]', FindTextOptions::new()->contextNode($dataDepartureElement)->preg('/Terminal\s(.*)/')->allowNull(true)->visible(false)->nonEmptyString());
                    $departureDate = $tab->findText('.//span[contains(text(), "Depart at")]/following-sibling::span', FindTextOptions::new()->contextNode($dataDepartureElement)->allowNull(true)->visible(false)->nonEmptyString());
                    $departureTime = $tab->findText('.//span[contains(text(), "Depart at")]', FindTextOptions::new()->contextNode($dataDepartureElement)->preg('/[\d:]+/')->allowNull(true)->visible(false)->nonEmptyString());
                }
                // Arrival info
                $arrivalAirportCode = $segmentsDataElement->getAttribute('data-arrival-airport-code');
                $dataArrivalElement = $tab->evaluate('//div[contains(@data-modal-name, "flight-' . $flightNumber . '")]//div[p[span[contains(text(), "Arrive")]]]', EvaluateOptions::new()->allowNull(True)->visible(false));
                if (isset($dataArrivalElement)) {
                    $arrivalTerminal = $tab->findText('.//span[contains(text(), "Terminal")]', FindTextOptions::new()->contextNode($dataArrivalElement)->preg('/Terminal\s(.*)/')->allowNull(true)->visible(false)->nonEmptyString());
                    $arrivalDate = $tab->findText('.//span[contains(text(), "Arrive at")]/following-sibling::span', FindTextOptions::new()->contextNode($dataArrivalElement)->allowNull(true)->visible(false)->nonEmptyString());
                    $arrivalTime = $tab->findText('.//span[contains(text(), "Arrive at")]', FindTextOptions::new()->contextNode($dataArrivalElement)->preg('/[\d:]+/')->allowNull(true)->visible(false)->nonEmptyString());
                }
                // General info
                if (isset($dataDepartureElement)) {
                    $aircraft = $tab->findText('.//span[contains(text(), "' . $flightNumber . '")]/following-sibling::span[3]', FindTextOptions::new()->contextNode($dataDepartureElement)->allowNull(true)->visible(false)->nonEmptyString());
                    $duration = $tab->findText('.//span[contains(@class, "-duration")]', FindTextOptions::new()->contextNode($dataDepartureElement)->allowNull(true)->visible(false)->nonEmptyString());
                    $cabin = $tab->findText('.//span[contains(text(), "' . $flightNumber . '")]/following-sibling::span[1]', FindTextOptions::new()->contextNode($dataDepartureElement)->allowNull(true)->visible(false)->nonEmptyString());
                }
                $seats = $tab->evaluate('//div[contains(@data-modal-name, "flight-' . $flightNumber . '")]//span[contains(text(), "Your seat number is")]/following-sibling::span', EvaluateOptions::new()->allowNull(true)->visible(false));

                $s = $f->addSegment();

                if (isset($airlineCode) && !empty($airlineCode)) {
                    $s->airline()->name($airlineCode);
                }
                if (isset($flightNumber) && !empty($flightNumber)) {
                    $arrayFlight[] = $airlineCode . $flightNumber;
                    $s->airline()->number($flightNumber);
                }
                if (isset($departureAirportCode) && !empty($departureAirportCode)) {
                    $s->departure()->code($departureAirportCode);
                }
                if (isset($departureDate, $departureTime) && !empty($departureDate) && !empty($departureTime)) {
                    $s->departure()->date2("{$departureDate} {$departureTime}");
                }
                if (isset($departureTerminal) && !empty($departureTerminal)) {
                    $s->departure()->terminal($departureTerminal);
                }
                if (isset($arrivalAirportCode) && !empty($arrivalAirportCode)) {
                    $s->arrival()->code($arrivalAirportCode);
                }
                if (isset($arrivalDate, $arrivalTime) && !empty($arrivalDate) && !empty($arrivalTime)) {
                    $s->arrival()->date2("{$arrivalDate} {$arrivalTime}");
                }
                if (isset($arrivalTerminal) && !empty($arrivalTerminal)) {
                    $s->arrival()->terminal($arrivalTerminal);
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
                if (!empty($airlineCode)) {
                    $s->extra()->seat($seats);
                }
            }
            $uniqueArrayFlight = array_unique($arrayFlight);
            $duplicates = count($arrayFlight) - count($uniqueArrayFlight);

            if ($duplicates > 0) {
                $this->notificationSender->sendNotification('check segments duplicate // MI');
            }
        } catch (\Exception $e) {
            $this->notificationSender->sendNotification('error cache // MI');
            $this->logger->error($e);
        } finally {
            $tab->stopCache();
        }
    }

    private function parseItinerary2025NewDesign(
        Tab $tab,
        Master $master
    ) {
        $this->logger->notice(__METHOD__);
        $confirmation = $tab->findText('//h3[contains(text(), "Booking Confirmation")]/following-sibling::span');
        $tab->logPageState();
        $f = $master->add()->flight();
        $f->general()->confirmation($confirmation, 'Booking reference');
        
        $passengerNames = $tab->findTextAll('//h3[contains(@id, "title") and contains(@id, "summary")]');
        $f->general()->travellers($passengerNames);

        $segmentsDataElements = $tab->evaluateAll('//div[contains(@id, "flight-detail-section") and contains(@class, "is-direct")]');
        foreach ($segmentsDataElements as $segmentsDataElement) {
            // General info
            $airlineCode = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "flight-number")]', FindTextOptions::new()->contextNode($segmentsDataElement)->preg('/(.*)\s/')->allowNull(true)->nonEmptyString());
            $flightNumber = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "flight-number")]', FindTextOptions::new()->contextNode($segmentsDataElement)->preg('/\s(.*)/')->allowNull(true)->nonEmptyString());
            $aircraft = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "plane-ref")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $duration = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "flight-duration")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $cabin = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "flight-class")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            // Departure info
            $departureAirportCode = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "origin-code")]');
            $departureTerminal = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "origin-terminal")]', FindTextOptions::new()->contextNode($segmentsDataElement)->preg('/Terminal\s(.*)/')->allowNull(true)->nonEmptyString());
            $departureDate = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "origin-date")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $departureTime = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "origin-time")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            // Arrival info
            $arrivalAirportCode = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "destination-code")]');
            $arrivalTerminal = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "destination-terminal")]', FindTextOptions::new()->contextNode($segmentsDataElement)->preg('/Terminal\s(.*)/')->allowNull(true)->nonEmptyString());
            $arrivalDate = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "destination-date")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());
            $arrivalTime = $tab->findText('.//span[contains(@id, "flight-detail-section") and contains(@id, "destination-time")]', FindTextOptions::new()->contextNode($segmentsDataElement)->allowNull(true)->nonEmptyString());

            if (isset($departureDate) && !isset($arrivalDate)) {
                $arrivalDate = $departureDate;
            }

            $s = $f->addSegment();
            if (isset($airlineCode)) {
                $s->airline()->name($airlineCode);
            }
            if (isset($flightNumber)) {
                $s->airline()->number($flightNumber);
            }
            if (isset($aircraft)) {
                $s->extra()->aircraft($aircraft);
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

    public function parseHistory(
        Tab $tab,
        Master $master,
        AccountOptions $accountOptions,
        ParseHistoryOptions $historyOptions
    ): void
    {
        try {
            $tab->showMessage(" ");
            $startDate = $historyOptions->getStartDate();
            $this->logger->debug('[History start date: ' . ($startDate ? $startDate->format('Y/m/d H:i:s') : 'all') . ']');
            $startDate = isset($startDate) ? $startDate->format('U') : 0;
            $statement = $master->getStatement() ?? $master->createStatement();
            $this->notificationSender->sendNotification("getHistory // MI");

            $transactions = $this->getHistory($tab);

            foreach ($transactions->transactions ?? [] as $transaction) {
                $date = $this->findPreg('/(\d{4}-\d+-\d+)T/', $transaction->dateProcessed);
                $description = $transaction->description;
                $points = $transaction->tierPoints;
                $avios = $transaction->aviosPoints;
                $this->addExpirationDate($description, $date, $avios, $statement);
                $time = strtotime($date);
                if (!$time) {
                    $this->logger->error("[SKIPPING ACTIVITY ROW]: date failed -> $date -> $time");
                    continue;
                }
                $row = [
                    "Transaction date" => $time,
                    /*
                    // refs #25044 note #44
                    "Posted date"      => $date,
                    */
                    "Description" => $description,
                    "Tier Points" => $points,
                    "Avios" => $avios,
                ];
                if (isset($startDate) && $time < $startDate) {
                    $this->logger->notice('[SKIPPING ACTIVITY ROW]: ' . print_r($row, true));
                    continue;
                }
                $this->logger->debug('[ADDING ACTIVITY ROW]: ' . print_r($row, true));
                $statement->addActivityRow($row);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        } finally {
            $tab->stopCache();
        }
    }

    public function parseHistoryV1(
        Tab $tab,
        Master $master,
        AccountOptions $accountOptions,
        ParseHistoryOptions $historyOptions
    ): void {
        try {
            $startDate = $historyOptions->getStartDate();
            $this->logger->debug('[History start date: ' . ($startDate ? $startDate->format('Y/m/d H:i:s') : 'all') . ']');
            $startDate = isset($startDate) ? $startDate->format('U') : 0;
            $statement = $master->getStatement() ?? $master->createStatement();

            // https://www.britishairways.com/nx/b/customerhub/en/gb/your-account/executive-statements?tab=membership
            $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/viewtransaction/execclub/_gf/%s?eId=172705',
                $accountOptions));

            $tab->evaluate('(//article[div[contains(@class, "personaldata")]])[1]',
                EvaluateOptions::new()->allowNull(true)->timeout(10));
            $tab->logPageState();

            // exp date #25044
            $nodes = $tab->evaluateAll('//article[div[contains(@class, "personaldata")]]');
            $nodesCount = count($nodes);
            $this->logger->debug("Total transactions found: " . $nodesCount);
            $this->watchdogControl->increaseTimeLimit(60);
            foreach ($nodes as $node) {
                $date = trim(str_replace('Transaction:', '', $tab->findText('./div[1]/div[3]/p | ./div[1]/div[2]/div[2]/p',
                    FindTextOptions::new()->contextNode($node)->allowNull(true))));
                $description = $tab->findText('./div[1]/div[2]/p | ./div[1]/div[2]/div[1]/p',
                    FindTextOptions::new()->contextNode($node)->allowNull(true));
                $avios = $tab->findText('./div[2]/div[1]/p[1]',
                    FindTextOptions::new()->contextNode($node)->allowNull(true));
                $this->addExpirationDate($description, $date, $avios, $statement);
            }

            $i = 1;
            while ($i <= 50) {
                $i++;
                if (in_array($i, [1,2,3])) {
                    $tab->logPageState();
                }
                $loadMore = $tab->evaluate('//button[@data-testid="load-more-button" or contains(text(), "Load more")]',
                    EvaluateOptions::new()->allowNull(true)->timeout(5));
                if (!isset($loadMore)) {
                    break;
                }
                $loadMore->click();
                $this->watchdogControl->increaseTimeLimit(120);
                sleep(1);
            }
            sleep(5);
            $tab->logPageState();
            $tab->startCache();
            $nodes = $tab->evaluateAll('//article[div[contains(@class, "personaldata")]]');
            $nodesCount = count($nodes);
            $this->logger->debug("Total transactions found: " . $nodesCount);
            if ($nodesCount >= 10) {
                $cookies = $tab->getCookies();
                foreach ($cookies as $key => $value) {
                    $this->logger->debug("$key = $value");
                }
                $this->notificationSender->sendNotification("cookie // MI");
            }
            foreach ($nodes as $node) {
                $date = trim(str_replace('Transaction:', '', $tab->findText('./div[1]/div[3]/p | ./div[1]/div[2]/div[2]/p',
                    FindTextOptions::new()->contextNode($node)->allowNull(true))));
                $description = trim($tab->findText('./div[1]/div[2]/p | ./div[1]/div[2]/div[1]/p',
                    FindTextOptions::new()->contextNode($node)->allowNull(true)));
                $points = $tab->findText('./div[2]/div[2]/p[1]',
                    FindTextOptions::new()->contextNode($node)->allowNull(true)->preg('/\d+/'));
                $avios = $tab->findText('./div[2]/div[1]/p[1]',
                    FindTextOptions::new()->contextNode($node)->allowNull(true));

                // 27/06/2025
                $date = str_replace('/', '-', $date);
                $time = strtotime($date);
                if (!$time) {
                    $this->logger->error("[SKIPPING ACTIVITY ROW]: date failed -> $date -> $time");
                    continue;
                }
                $row = [
                    "Transaction date" => $time,
                    /*
                    // refs #25044 note #44
                    "Posted date"      => $date,
                    */
                    "Description" => $description,
                    "Tier Points" => $points,
                    "Avios" => (int)$avios,
                ];
                if (isset($startDate) && $time < $startDate) {
                    $this->logger->notice('[SKIPPING ACTIVITY ROW]: ' . print_r($row, true));
                    continue;
                }
                $this->logger->debug('[ADDING ACTIVITY ROW]: ' . print_r($row, true));
                $statement->addActivityRow($row);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
    }

    private function addExpirationDate($activity, $date, $avios, $statement) {
        $this->logger->notice(__METHOD__);
        if($statement->getExpirationDate()) {
            $this->logger->debug('Exp date is already set');
            return;
        }

        // refs #7665 - ignore certain activities
        if ($this->ignoreActivity($activity)) {
            $this->logger->debug('activity ignored');
            return;
        }
        // refs #9168 - ignore row with empty avios
        $this->logger->debug("Date {$date} / avios {$avios}");

        $reference = $this->findPreg("/Reference:\s*([^\s]+)/ims", $activity);

        if (strpos($activity, "Avios refund") !== false || isset($this->ignoreBookings[$reference])) {
            $this->logger->debug("Booking Reference: {$reference}");

            if (isset($this->ignoreBookings[$reference])) {
                if ($this->ignoreBookings[$reference] == -floatval($avios)) {
                    $this->logger->notice("Skip Avios refund: {$reference}");

                    return;
                } else {
                    $this->logger->notice("First transaction not found: {$reference}");
                }
            } else {
                $this->logger->notice("Add Avios refund to ignore transactions: {$reference}");
                $this->ignoreBookings[$reference] = $avios;

                return;
            }
        }

        if (
            !str_starts_with($avios, 0) && !strstr($avios, '-')
        ) {
            $exp = strtotime($date);
            if (!$exp) {
                $this->logger->debug('invalid timestamp');
                return;
            }
            $statement->addProperty('LastActivity', $exp);
            $statement->setExpirationDate(strtotime("+3 year", $exp));
        }
    }
    /**
     * NOTICE: Do not touch, it may be useful in future
     * @param \AwardWallet\ExtensionWorker\Tab $tab
     * @param \AwardWallet\Schema\Parser\Component\Master $master
     * @param \AwardWallet\ExtensionWorker\AccountOptions $accountOptions
     * @param \AwardWallet\ExtensionWorker\ParseHistoryOptions $historyOptions
     * @return void
     */    
    public function parseHistoryOld(Tab $tab, Master $master, AccountOptions $accountOptions, ParseHistoryOptions $historyOptions): void
    {
        try {
            $startDate = $historyOptions->getStartDate();
            $this->logger->debug('[History start date: ' . ($startDate ? $startDate->format('Y/m/d H:i:s') : 'all') . ']');
            $startDate = isset($startDate) ? $startDate->format('U') : 0;
            $statement = $master->getStatement() ?? $master->createStatement();

            $tab->gotoUrl($this->getCountryUrl('https://www.britishairways.com/travel/viewtransaction/execclub/_gf/%s?eId=172705',
                $accountOptions));
            
            $message = $tab->evaluate('
        //p[contains(text(),"You have no recent transactions.")] | //a[@id="paxDetailAccordion"] | //input[@id="dateRangeRadio"]',
                EvaluateOptions::new()->allowNull(true)->timeout(10));
            if (!isset($message)) {
                $this->logger->error("something went wrong");
                return;
            }
            if (stristr($message->getInnerText(), 'You have no recent transactions.')) {
                $this->logger->notice($message->getInnerText());
                return;
            }
            $tab->evaluate('//a[@id="paxDetailAccordion"]', EvaluateOptions::new()->visible(false))->click();
            $tab->evaluate('//input[@id="dateRangeRadio"]', EvaluateOptions::new()->visible(false))->click();
            $tab->evaluate('//select[@id="from_day"]', EvaluateOptions::new()->visible(false))->setValue(date("d",
                strtotime("+1 day", time())));
            $tab->evaluate('//select[@id="from_month"]', EvaluateOptions::new()->visible(false))->setValue(date("m"));
            $tab->evaluate('//select[@id="from_year"]', EvaluateOptions::new()->visible(false))->setValue(date("Y",
                strtotime("-3 year", time())));
            $tab->evaluate('//form[@id="transForm"]//input[@value = "Search"]',
                EvaluateOptions::new()->visible(false))->click();
            sleep(2);
            $this->parsePageHistory($tab, $statement, $startDate);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->notificationSender->sendNotification("error history // MI");        }
        /*$options = [
            'method'  => 'post',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'from_day'         => date("d", strtotime("+1 day", time())),
                'from_month'       => date("m"),
                'from_year'        => date("Y", strtotime("-3 year", time())),
                'search_type'      => 'D',
                'to_day'           => date("d"),
                'to_month'         => date("n"),
                'to_year'          => date("Y"),
                'transaction_type' => '0',
            ]),
        ];
        $this->logger->debug($options['body']);

        $loyaltyStatement = $tab->fetch("https://www.choicehotels.com/webapi/user-account/loyalty-statement",
            $options)->body;

        $options = [
            'method'  => 'post',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'from_day'         => 'val',
            ]),
        ];
        $tab->fetch("https://www.british.com/loyalty-statement", $options)->setBody();*/
    }

    public function isActiveTab(AccountOptions $options): bool
    {
        return true;
    }

    /**
     * NOTICE: Do not touch, it may be useful in future
     * @param \AwardWallet\ExtensionWorker\Tab $tab
     * @param \AwardWallet\Schema\Parser\Common\Statement $statement
     * @param mixed $startDate
     * @return void
     */
    private function parsePageHistory(Tab $tab, Statement $statement, $startDate)
    {
        $this->logger->notice(__METHOD__);
        $result = [];
        $tab->evaluate("(//div[@class='info-detail-main-transaction-row'])[1]", EvaluateOptions::new()->timeout(30)->allowNull(true));
        $nodes = $tab->evaluateAll("//div[@class='info-detail-main-transaction-row']");
        $this->logger->debug("Total transactions found: " . count($nodes));
        //$tab->saveScreenshot();
        foreach ($nodes as $key => $node) {
            $this->logger->debug("key: $key");
            // ---------------------- Cabin Bonus, Tier Bonus, Flights ----------------------- #
            // TODO: wtf?
            $postDate = $tab->findTextNullable("./div[@class='info-detail-item post']/p", FindTextOptions::new()->visible(false)->contextNode($node));

            if ($postDate == '') {
                /*$k = $i;

                while ($this->http->FindSingleNode("div[starts-with(@id,'resultRow')][2]/p", $nodes->item($k)) == '' && $k > 0) {
                    $k--;
                }
                $postDate = strtotime(str_replace('Transaction:', '', $this->http->FindSingleNode("div[starts-with(@id,'resultRow')][2]/p[1]", $nodes->item($k))));
                $transactionDate = strtotime(str_replace('Transaction:', '', $this->http->FindSingleNode("div[starts-with(@id,'resultRow')][1]/p[1]", $nodes->item($k))));*/
            } else {
                $postDate = strtotime($postDate);
                $transactionDate = strtotime(str_replace('Transaction:', '',
                    $tab->findText("./div[starts-with(@id,'resultRow')][1]/p[1]",
                        FindTextOptions::new()->contextNode($node))));
            }
            // ----------------------------------------------------------------------------- #

            if (isset($startDate) && $postDate < $startDate) {
                continue;
            }
            $result['Transaction date'] = $transactionDate;
            $result['Posted date'] = $postDate;
            $result['Description'] = $tab->findText("div[starts-with(@id,'resultRow')][3]/p",
                FindTextOptions::new()->contextNode($node)->timeout(0));
            $result['Tier Points'] = intval(str_replace(',', '',
                $tab->findText("div[starts-with(@id,'resultRow')][4]/p[last()]",
                    FindTextOptions::new()->contextNode($node)->timeout(0))));
            $result['Avios'] = intval(str_replace(',', '', $tab->findText("div[starts-with(@id,'resultRow')][5]/p[2]",
                FindTextOptions::new()->contextNode($node)->timeout(0))));
            $statement->addActivityRow($result);
            $this->watchdogControl->increaseTimeLimit();
        }
    }

    private function ignoreActivity($activity)
    {
        foreach ($this->activityIgnore as $ignore) {
            if (strpos($activity, $ignore) !== false) {
                return true;
            }
        }

        return false;
    }

    private function gerMe(Tab $tab)
    {
        $cookies = $tab->getCookies();
        if (!isset($cookies['token'])) {
            $this->logger->error('token is not defined in cookies');
            return null;
        }
        $this->logger->debug("[token]: {$cookies['appSessionNX']}");
        $options = [
            'method'  => 'get',
            'headers' => [
                'Accept'                    => 'application/json, application/javascript',
                'Content-Type'              => 'application/json',
                'x-ba-action-name' => 'getme',
                'x-ba-application-name' => 'customer-hub',
                'x-ba-channel' => 'WEB',
                'x-ba-client-name' => 'customer-hub-web',
                'x-ba-language' => 'en',
                'x-ba-market' => 'gb',
                'x-ba-device-id' => '04dbd1d1-491d-4a82-a997-c4f27227e047',
                'x-ba-interaction-id' => '04578ef0-27f2-4111-9b21-96a4993fbfca',
                'x-ba-request-id' => '887e5fb0-604f-4399-9dc9-1fe81d604e17',
                'x-ba-track-id' => '76991a43-71c7-41c5-91af-c801cf287c33',
                'x-ba-user-anon-id' => 'fda085dc-5db7-4386-8fcf-1ca717f48a6d',
                'Authorization' => "Bearer {$cookies['appSessionNX']}",
            ],
        ];

        try {
            $json = $tab->fetch('https://www.britishairways.com/nx/b/bff/customer-account/v0/me',
                $options)->body;
            $this->logger->info($json);

            return json_decode($json);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }

    private function getAccount(Tab $tab, string $account)
    {
        $cookies = $tab->getCookies();
        if (!isset($cookies['token'])) {
            $this->logger->error('token is not defined in cookies');
            return null;
        }
        $this->logger->debug("[token]: {$cookies['appSessionNX']}");
        $options = [
            'method'  => 'get',
            'headers' => [
                'Accept'                    => 'application/json, application/javascript',
                'Content-Type'              => 'application/json',
                'x-ba-action-name' => 'getme',
                'x-ba-application-name' => 'customer-hub',
                'x-ba-channel' => 'WEB',
                'x-ba-client-name' => 'customer-hub-web',
                'x-ba-language' => 'en',
                'x-ba-market' => 'gb',
                'x-ba-device-id' => '04dbd1d1-491d-4a82-a997-c4f27227e047',
                'x-ba-interaction-id' => '04578ef0-27f2-4111-9b21-96a4993fbfca',
                'x-ba-request-id' => '887e5fb0-604f-4399-9dc9-1fe81d604e17',
                'x-ba-track-id' => '76991a43-71c7-41c5-91af-c801cf287c33',
                'x-ba-user-anon-id' => 'fda085dc-5db7-4386-8fcf-1ca717f48a6d',
                'Authorization' => "Bearer {$cookies['appSessionNX']}",
            ],
        ];

        try {
            $json = $tab->fetch("https://www.britishairways.com/nx/b/bff/customer-hub/v0/accounts/$account/v2",
                $options)->body;
            $this->logger->info($json);

            return json_decode($json);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }

    private function getHistory(Tab $tab)
    {
        $cookies = $tab->getCookies();
        if (!isset($cookies['token'])) {
            $this->logger->error('token is not defined in cookies');
            return null;
        }
        $this->logger->debug("[token]: {$cookies['appSessionNX']}");
        $options = [
            'method'  => 'get',
            'headers' => [
                'Accept'                    => 'application/json, application/javascript',
                'Content-Type'              => 'application/json',
                'x-ba-action-name' => 'getstatements',
                'x-ba-application-name' => 'customer-hub',
                'x-ba-channel' => 'WEB',
                'x-ba-client-name' => 'customer-hub-web',
                'x-ba-language' => 'en',
                'x-ba-market' => 'gb',
                'x-ba-device-id' => '04dbd1d1-491d-4a82-a997-c4f27227e047',
                'x-ba-interaction-id' => '04578ef0-27f2-4111-9b21-96a4993fbfca',
                'x-ba-request-id' => '887e5fb0-604f-4399-9dc9-1fe81d604e17',
                'x-ba-track-id' => '76991a43-71c7-41c5-91af-c801cf287c33',
                'x-ba-user-anon-id' => 'fda085dc-5db7-4386-8fcf-1ca717f48a6d',
                'Authorization' => "Bearer {$cookies['appSessionNX']}",
            ],
        ];

        try {
            $json = $tab->fetch('https://www.britishairways.com/nx/b/bff/customer-hub/v0/members/AHWK/transactions?startRecord=1&offset=10000',
                $options)->body;
            //$this->logger->info($json);

            return json_decode($json);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }

    private function getSegmentFromPreParse(\AwardWallet\Schema\Parser\Common\Flight $r, array $preParse)
    {
        $this->logger->notice(__METHOD__);

        if (!isset($preParse['airline'])) {
            return;
        }
        $s = $r->addSegment();
        $s->airline()
            ->name($preParse['airline'])
            ->number($preParse['flight']);
        $s->departure()
            ->noCode()
            ->name($preParse['depName'])
            ->date($preParse['depDate']);
        $s->arrival()
            ->noCode()
            ->name($preParse['arrName'])
            ->date($preParse['arrDate']);
    }

    private function parseItinerary(
        Tab $tab,
        Master $master,
        ?string $pnr = null,
        ?array $preParseCancelled = []
    ): ?string {
        try {
            $this->logger->notice(__METHOD__);
            $tab->evaluate("//h1[starts-with(normalize-space(),'Booking') and ./strong]");

            if ($err = $tab->findTextNullable("//ul/li[contains(text(), 'Not able to connect to AGL Group Loyalty Platform and IO Error Recieved')]")) {
                $this->logger->error("Skipping: $err");

                return $err;
            }

            if ($tab->findTextNullable("//h1[starts-with(normalize-space(),'Booking') and ./strong]")) {
                return $this->parseItinerary2021($tab, $master, $pnr, $preParseCancelled);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->notificationSender->sendNotification($e->getMessage() . " // MI");
        }
        return null;
    }

    private function parseItinerary2021(Tab $tab, Master $master, ?string $pnr = null, ?array $preParseCancelled = []): ?string
    {
        $this->logger->notice(__METHOD__);
        $segments = $tab->evaluateAll("//div[starts-with(normalize-space(@data-modal-name),'flight')]");

        if (count($segments) == 0 && $tab->findTextNullable("//h2[starts-with(normalize-space(),'Where will your eVoucher take you?')]")) {
            $this->logger->notice('Skip: Where will your eVoucher take you?');

            return null;
        }

        $conf = $tab->findText("//h1[starts-with(normalize-space(),'Booking')]/strong");
        $f = $master->add()->flight();
        $f->general()->confirmation($conf, 'Booking');

        if ($tab->findTextNullable("//p[contains(text(),\"We're replacing your booking with the voucher, so you'll no longer be able to use your\")]/strong") === $conf) {
            $this->logger->debug($tab->findTextNullable("//p[contains(text(),\"We're replacing your booking with the voucher, so you'll no longer be able to use your\")]"));
            $f->general()
                ->status('Cancelled')
                ->cancelled();

            if (isset($preParseCancelled[$conf])) {
                $this->getSegmentFromPreParse($f, $preParseCancelled[$conf]);
            }
        }
        $pax = array_unique(array_filter(
            $tab->findTextAll("(//div[starts-with(normalize-space(@data-modal-name),'flight')])/descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[2]//h5",
            FindTextOptions::new()->visible(false))
        ));

        if (!empty($pax) && !$f->getCancelled()) {
            $f->general()->travellers($pax, true);
        }
        $this->logger->info(sprintf("[%s] Parse Flight #%s", $this->itCount++, $conf), ['Header' => 3]);
        $this->watchdogControl->increaseTimeLimit(300);

        $flightsArrayList = explode('/trackFlightsArrayList.push(trackflightArray);/',
            $tab->findText('//div[contains(@class,"js-main-content is-visible")]/script[contains(text(),"trackFlightsArrayList.push")]',
                FindTextOptions::new()->visible(false)));
        $this->logger->debug("flightsArrayList " . count($flightsArrayList) . ' found');

        $segments = $tab->evaluateAll('//div[starts-with(normalize-space(@data-modal-name),"flight")]',
            EvaluateOptions::new()->visible(false));
        $this->logger->debug("segments " . count($segments) . ' found');

        foreach ($segments as $i => $segment) {
            $s = $f->addSegment();
            $route = $tab->findText("./descendant::div[contains(@class,'flight-itinerary')][1]", FindTextOptions::new()->contextNode($segment)->visible(false));
            $points = explode(' to ', $route);

            if (count($points) !== 2) {
                $this->logger->error("check parse segment $i");
            }
            $flight = $tab->findText("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p/following-sibling::div[2]/span[1]", FindTextOptions::new()->contextNode($segment)->visible(false));
            $operator = $tab->findText("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p/following-sibling::div[3]/span[contains(.,'Operated by')]",
                FindTextOptions::new()->contextNode($segment)->preg("/Operated by (.+)/")->visible(false));

            if (strlen($operator) > 50) {
                if (stripos($operator, 'AMERICAN AIRLINES (AA) ') !== false) {
                    $operator = 'American Airlines';
                }
            }
            $s->airline()
                ->name($this->findPreg("/^([A-Z\d][A-Z]|[A-Z][A-Z\d])\s*\d+$/", $flight))
                ->number($this->findPreg("/^(?:[A-Z\d][A-Z]|[A-Z][A-Z\d])\s*(\d+)$/", $flight))
                ->operator($operator)
                ->confirmation($tab->findText("./descendant::text()[normalize-space()!=''][1]/ancestor::h1", FindTextOptions::new()->contextNode($segment)->visible(false)));

            if (isset($flightsArrayList[$i]) && $this->findPreg("/\.flightnumber = '{$flight}';/", $flightsArrayList[$i])) {
                $this->logger->debug($flightsArrayList[$i]);
                $s->departure()
                    ->code($this->findPreg("/\.airportfrom = '([A-Z]{3})';/", $flightsArrayList[$i]));
                $s->arrival()
                    ->code($this->findPreg("/\.airportto = '([A-Z]{3})';/", $flightsArrayList[$i]));
                $s->extra()
                    ->bookingCode($this->findPreg("/\.sellingclass = '([A-Z]{1,2})';/", $flightsArrayList[$i]));
            } else {
                $s->departure()->noCode();
                $s->arrival()->noCode();
            }
            $depDate = $tab->findText("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p[1]",
                FindTextOptions::new()->contextNode($segment)->preg("/Depart at (.+)/")->visible(false));
            $arrDate = $tab->findText("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p[contains(.,'Arrive')][1]",
                FindTextOptions::new()->contextNode($segment)->preg("/Arrive at (.+)/")->visible(false));
            $stop = $tab->findTextNullable("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p[2]",
                FindTextOptions::new()->contextNode($segment)->preg("/^(\d+) stop/")->visible(false));

            if ($stop) {
                $s->extra()->stops($stop);
            }
            $s->departure()
                ->date2(preg_replace("/^(\d+:\d+), (.+)$/", '$2, $1', $depDate))
                ->name($tab->findText("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p[1]/following-sibling::div[1]/descendant::text()[1]", FindTextOptions::new()->contextNode($segment)->visible(false)))
                ->terminal($tab->findTextNullable("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p[1]/following-sibling::div[1]//span",
                    FindTextOptions::new()->contextNode($segment)->preg("/Terminal\s+(.+)/")->visible(false)), false, true);
            $s->arrival()
                ->date2(preg_replace("/^(\d+:\d+), (.+)$/", '$2, $1', $arrDate))
                ->name($tab->findText("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p[contains(.,'Arrive')][1]/following-sibling::div[1]/descendant::text()[1]", FindTextOptions::new()->contextNode($segment)->visible(false)))
                ->terminal($tab->findTextNullable("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p[contains(.,'Arrive')][1]/following-sibling::div[1]//span", FindTextOptions::new()->contextNode($segment)->preg("/Terminal\s+(.+)/")->visible(false)), false, true);
            $s->extra()
                ->duration($tab->findTextNullable("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p/following-sibling::div[3]/span[contains(@class,'duration')][1]", FindTextOptions::new()->contextNode($segment)->visible(false)), false, true)
                ->cabin(preg_replace('/\([\w\s]+\)/', '', $tab->findTextNullable("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p/following-sibling::div[2]/span[2]", FindTextOptions::new()->contextNode($segment)->visible(false))), true, true)
                ->status($tab->findTextNullable("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p/following-sibling::div[2]/span[3][not(contains(.,'Information only'))]", FindTextOptions::new()->contextNode($segment)->visible(false)), true, true)
                ->aircraft($tab->findTextNullable("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[1]/descendant::p/following-sibling::div[2]/span[4]", FindTextOptions::new()->contextNode($segment)->visible(false)), false, true)
                ->seats(array_unique($tab->findTextAll("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[2]//h5/following-sibling::div[1]//h6[contains(.,'Seating')]/following::p[1]/span[2]", FindTextOptions::new()->contextNode($segment)->visible(false))))
                ->meals($tab->findTextAll("./descendant::div[contains(@class,'flight-itinerary')][1]/following-sibling::div[2]//h5/following-sibling::div[1]//h6[contains(.,'Meal')]/following::p[1][not(contains(.,'Please try again later'))]", FindTextOptions::new()->contextNode($segment)->visible(false)));

            if (stripos($s->getStatus(), 'cancelled') !== false || stripos($s->getStatus(), 'canceled') !== false) {
                $s->extra()->cancelled();
            }
        }
        $this->logger->debug('Parsed Flight:');
        $this->logger->debug(var_export($f->toArray(), true), ['pre' => true]);

        return null;
    }

    private function parsePastItineraries(Tab $tab, Master $master, AccountOptions $options)
    {
        $this->logger->notice(__METHOD__);
        $this->logger->info("Past Itineraries", ['Header' => 2]);
        $this->notificationSender->sendNotification('check past it// MI');
        $tab->gotoUrl($this->getCountryUrl(
            "https://www.britishairways.com/travel/viewaccount/execclub/_gf/%s?eId=106062&source=EXEC_LHN_PASTBOOKINGS",
            $options));
        
        $pastOrFailed = $tab->evaluate("(//div[@class = 'past-book']/div[contains(@class, 'airport-arrival')])[1]",
            EvaluateOptions::new()->allowNull(true));
        if (!$pastOrFailed) {
            return;
        }
        $pastIts = $tab->evaluateAll("//div[@class = 'past-book']/div[contains(@class, 'airport-arrival')]",
            EvaluateOptions::new()->allowNull(true));
        $this->logger->debug("Total ".count($pastIts)." past reservations found");

        if (count($pastIts) == 0) {
            $this->logger->notice(">>> " . $this->findPreg("/We can't find any bookings for this account in the last 12 months\./ims", $tab->getHtml(0)));
        }

        foreach ($pastIts as $node) {
            $header = $tab->findTextNullable("./h4/span[1]", FindTextOptions::new()->contextNode($node));
            $f = $master->add()->flight();
            $f->general()
                ->confirmation($tab->findTextNullable(".//p[contains(@class, 'booking-value')]", FindTextOptions::new()->contextNode($node)));
            $s = $f->addSegment();
            $s->airline()
                ->name($this->findPreg("/^(\w{2})\d+/",  $header))
                ->number($this->findPreg("/^\w{2}(\d+)/", $header));
            $s->departure()
                ->noCode()
                ->date(strtotime($tab->findTextNullable(".//p[contains(@class, 'departure-value')]", FindTextOptions::new()->contextNode($node))))
                ->name($this->FindPreg("/^[A-Z\d]{2}+\d+\s+(.+)\s+to\s+.+/", $header));
            $s->arrival()
                ->noCode()
                ->date(strtotime($tab->findTextNullable(".//p[contains(@class, 'arrival-value')]", FindTextOptions::new()->contextNode($node))))
                ->name($this->findPreg("/^[A-Z\d]{2}+\d+\s+.+\s+to\s+(.+)/", $header));
        }
        return;
    }

    public function getLoginWithConfNoStartingUrl(array $confNoFields, ConfNoOptions $options): string
    {
        return  'https://www.britishairways.com/travel/managebooking/execclub/_gf/en_us?eId=1';
    }

    /**
     * @param \AwardWallet\ExtensionWorker\Tab $tab
     * @param array $confNoFields
     * @param \AwardWallet\ExtensionWorker\ConfNoOptions $options
     * @return LoginWithConfNoResult
     */
    public function loginWithConfNo(Tab $tab, array $confNoFields, ConfNoOptions $options): LoginWithConfNoResult
    {
        $this->logger->notice(__METHOD__);
        //$this->notificationSender->sendNotification('loginWithConfNo // MI');
        $el = $tab->evaluate('
            //input[@id="bookingRef"]
            | //label[span[contains(text(),"I have consent from the passengers on this booking to access and manage their personal information.")]]
            | //strong[@class="personaldata"]
        ');
        // I have consent from the passengers on this booking to access and manage their personal information.
        if ($el->getNodeName() == 'LABEL') {
            $el->click();
            $tab->evaluate('//button[contains(text(),"Continue")]')->click();
        } else {
            if ($el->getNodeName() == 'LABEL') {
                $tab->evaluate('//a[div[contains(@class, "exit")]]')->click();
            }            
            $tab->querySelector('#bookingRef')->setValue($confNoFields['ConfNo']);
            $tab->querySelector('#lastname')->setValue($confNoFields['LastName']);
            $tab->querySelector('#findbookingbuttonsimple')->click();
        }

        $submitResult = $tab->evaluate('
            //*[@id="appErrors"]
            | //h3[@class ="next-flight__text"]
            | //h1[@id="hero-title"]
            | //label[span[contains(text(),"I have consent from the passengers on this booking to access and manage their personal information.")]]
        ');
        if ($submitResult->getAttribute("id") === 'appErrors') {
            return LoginWithConfNoResult::error($submitResult->getInnerText());
        }
        // I have consent from the passengers on this booking to access and manage their personal information.
        if (
            stristr($submitResult->getInnerText(), 'I have consent from the passengers on this booking to access and manage their personal information.')
        ) {
            $submitResult->click();
            $tab->evaluate('//button[contains(text(),"Continue")]')->click();
        }

        return LoginWithConfNoResult::success();
    }

    public function retrieveByConfNo(Tab $tab, Master $master, array $fields, ConfNoOptions $options): void
    {
        $this->logger->notice(__METHOD__);
        /*
        $el = $tab->evaluate('//h3[@class ="next-flight__text"]');
        if (
            strstr($el->getAttribute('class'), 'next-flight__text')
        ) {
            $this->parseItinerary2025OldDesign($tab, $master, $fields['ConfNo']);
        }
        */
        
        $el = $tab->evaluate('//strong[@class="personaldata"] | //h3[@id="text-custom" and contains(text(), "Booking Confirmation")]');
        if ($el->getNodeName() == 'STRONG') {
            $this->parseItinerary2025OldDesign($tab, $master);
        } else {
            $this->parseItinerary2025NewDesign($tab, $master);
        }
    }

    public function continueLogin(Tab $tab, Credentials $credentials): LoginResult
    {
        $tab->evaluate('//input[@id = "code"]')->setValue($credentials->getAnswers()[$this->stateManager->get('question')]);
        $tab->evaluate('//button[@type = "submit" and contains(text(), "Continue")]')->click();

        $errorOrSuccess = $tab->evaluate('//span[@id = "error-element-code"] 
            | //p[contains(text(), "Your membership number")]
            | //p[contains(text(), "Membership number:")]/following-sibling::p
            | //span[@id="membershipNumberValue"]
            | //span[@data-testid="membership-number"]
        ');

        if ($errorOrSuccess->getAttribute("id") === 'error-element-code') {
            $question = $this->getQuestion($tab);
            if ($question) {
                return LoginResult::question($question, $errorOrSuccess->getInnerText());
            }
        }

        return LoginResult::success();
    }

    private function getQuestion(Tab $tab) : ?string
    {
        $questionNode = $tab->evaluate('//p[@id = "aria-description-text"]', EvaluateOptions::new()->allowNull(true)->timeout(0));
        if (!$questionNode) {
            $this->stateManager->delete('question');

            return null;
        }

        $this->stateManager->keepBrowserSession(true);
        $questionText = $questionNode->getInnerText();
        $this->stateManager->set('question', $questionText);

        return $questionText;
    }
}
