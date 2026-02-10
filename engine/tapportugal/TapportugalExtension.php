<?php

namespace AwardWallet\Engine\tapportugal;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
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
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;
use CheckException;

class TapportugalExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ParseItinerariesInterface, ParseHistoryInterface
{
    use TextTrait;
    public const XPATH_LOGIN_RESULT = '//p[contains(@id, "form-item-message")]
                | //div[@aria-label="Notifications (F8)"]//div[contains(@class, "font-semibold")]
                | //span[contains(text(), "CPF")]/span
                | //div[*[*[@id="Miles&Go"]]]/following-sibling::div/div[span[div]]/span[div[span]]';
    private $history;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.flytap.com/en-us/my-account';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('//input[@name="username"] | //span[contains(text(), "CPF")]/span | //div[*[*[@id="Miles&Go"]]]/following-sibling::div/div[span[div]]/span[div[span]]');

        return $el->getNodeName() == "SPAN";
    }

    public function getLoginId(Tab $tab): string
    {
        if (
            !strstr($tab->getUrl(), "my-account")
        ) {
            $tab->gotoUrl('https://www.flytap.com/en-us/my-account');
        }

        return $tab->findText('//span[contains(text(), "CPF")]/span | //div[*[*[@id="Miles&Go"]]]/following-sibling::div/div[span[div]]/span[div[span]]', FindTextOptions::new()->nonEmptyString());
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@name="username"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@autocomplete="current-password"]');
        $password->setValue($credentials->getPassword());

        $tab->evaluate('//form[div[div[input[@name="username"]]]]//div[button and input[@type="checkbox"]]/button')->click();

        $turnstile = $tab->evaluate('//div[@id="cf-turnstile"]', EvaluateOptions::new()->timeout(3)->allowNull(true));

        if ($turnstile) {
            $tab->showMessage(Message::captcha('Login'));
            $submitResult = $tab->evaluate(self::XPATH_LOGIN_RESULT, EvaluateOptions::new()->timeout(120)->allowNull(true));

            if (!$submitResult) {
                return LoginResult::captchaNotSolved();
            }
        } else {
            $tab->evaluate('//form/div[div[@id="cf-turnstile"]]/following-sibling::div//button[@type="submit"]')->click();
            $submitResult = $tab->evaluate(self::XPATH_LOGIN_RESULT);
        }

        if ($submitResult->getNodeName() == 'SPAN') {
            return new LoginResult(true);
        }

        if ($submitResult->getNodeName() == 'P') {
            $error = $tab->findText('//p[contains(@id, "form-item-message")]//span[@class="text-content-neutral"]');

            return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
        }

        if ($submitResult->getNodeName() == 'DIV') {
            $message = $submitResult->getInnerText();

            if (
                strstr($message, "An error occurred while logging in. Please check your details and try again.")
            ) {
                return new LoginResult(false, $message, null, ACCOUNT_INVALID_PASSWORD);
            }

            if (
                strstr($message, "Welcome to TAP Air Portugal!")
                || strstr($message, "Bem-vindo à TAP Air Portugal|")
            ) {
                return new LoginResult(true);
            }

            return new LoginResult(false, $message);
        }

        return new LoginResult(false);
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->evaluate('//div[contains(@class, "account-trigger")]')->click();
        $tab->evaluate('//button[span[span[contains(text(), "logout")]]]')->click();
        $tab->evaluate('//ol/li/div/div');
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $data = $tab->getFromSessionStorage('account-storage');
        $this->logger->info($data);
        $data = json_decode($data);

        if (empty($data)) {
            return;
        }

        if (isset($data->state->busy) && $data->state->busy) {
            /*
            throw new CheckRetryNeededException(3, 0, self::PROVIDER_ERROR_MSG);
            */
            throw new CheckException('The website is experiencing technical difficulties, please try to check your balance at a later time.', ACCOUNT_PROVIDER_ERROR);
        }

        $this->history = $data->state->loyaltyAccountDetails->MemberMileageStatus->MileageTransactions->MileageTransaction ?? [];
        $LoyaltyAccount = $data->state->loyaltyAccountDetails ?? $data->state->customerDetails->LoyaltyAccount;

        $statement = $master->createStatement();
        // Balance - Miles Balance
        $statement->SetBalance($LoyaltyAccount->MemberMileageStatus->TotalMiles);
        // Client Number
        $statement->addProperty("AccountNumber", $data->state->customerDetails->CustLoyalty[0]->MembershipID);
        // Name
        $statement->addProperty("Name", beautifulName($data->state->customerDetails->FullName));

        // Status
        $statement->addProperty('Status', $LoyaltyAccount->MemberAccountInfo->LoyalLevel);
        // Status Miles
        $statement->addProperty('StatusMiles', $LoyaltyAccount->MemberMileageStatus->StatusMiles);

        $exp = null;

        foreach ($LoyaltyAccount->MemberMileageStatus->ExpiredMIles ?? [] as $item) {
            $date = $item->ExpirationDate;

            if (!isset($exp) && $date || $exp > strtotime($date)) {
                $exp = strtotime($date);
                $statement->SetExpirationDate($exp);
                // Expiration Date
                $statement->addProperty("MilesToExpire", $item->Amount);
            }
        }
    }

    public function parseItineraries(
        Tab $tab,
        Master $master,
        AccountOptions $options,
        ParseItinerariesOptions $parseItinerariesOptions
    ): void {
        $tab->gotoUrl('https://myb.flytap.com/my-bookings');
        $tab->evaluate('//div[@class="empty-trips-notice"] | (//app-booked-trip)[1]', EvaluateOptions::new()->timeout(10)->allowNull(true));
        $userData = $tab->getFromSessionStorage('userData');

        if ($this->findPreg('/,"userPnrs":\[]/', $userData)) {
            $master->setNoItineraries(true);

            return;
        }

        $userData = json_decode($userData);
        $headers = [
            "Content-Type"     => "application/json",
            "Accept"           => "application/json, text/plain, */*",
            'Origin'           => 'https://myb.flytap.com',
        ];
        $data = '{"clientId":"-bqBinBiHz4Yg+87BN+PU3TaXUWyRrn1T/iV/LjxgeSA=","clientSecret":"DxKLkFeWzANc4JSIIarjoPSr6M+cXv1rcqWry2QV2Azr5EutGYR/oJ79IT3fMR+qM5H/RArvIPtyquvjHebM1Q==","referralId":"h7g+cmbKWJ3XmZajrMhyUpp9.cms35","market":"US","language":"en-us","userProfile":null,"appModule":"0"}';

        try {
            $options = [
                'method'      => 'post',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
                'body'        => $data,
            ];
            $json = $tab->fetch("https://myb.flytap.com/bfm/rest/session/create", $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }
        $this->logger->debug($json);
        $response = json_decode($json);
        $userPnrs = $userData->userPnrs ?? [];

        foreach ($userPnrs as $item) {
            $this->logger->info(sprintf('Parse Itinerary #%s', $item->pnr), ['Header' => 3]);
            //$this->http->GetURL("https://myb.flytap.com/my-bookings/details/$item->pnr/$item->lastname");
            $headers = [
                "Accept"        => "application/json, text/plain, */*",
                'Content-Type'  => 'application/json',
                'Origin'        => 'https://myb.flytap.com',
                'Referer'       => "https://myb.flytap.com/my-bookings/details/$item->pnr/$item->lastname",
                "Authorization" => "Bearer " . ($this->tokenProperties ?? $response->id),
            ];
            $data = [
                'lastName'  => $item->lastname,
                'pnrNumber' => $item->pnr,
            ];

            try {
                $options = [
                    'method'      => 'post',
                    'cors'        => 'no-cors',
                    'credentials' => 'omit',
                    'headers'     => $headers,
                    'body'        => json_encode($data),
                ];
                $json = $tab->fetch("https://myb.flytap.com/bfm/rest/booking/pnrs/search?skipAncillariesCatalogue=true", $options)->body;
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());

                return;
            }

            $this->logger->debug($json);
            $response = json_decode($json);

            if ($this->parseReservationFlytap_2($response, $master) === false) {
                $master->add()->flight(); // for broke result

                return;
            }
        }
    }

    public function parseHistory(
        Tab $tab,
        Master $master,
        AccountOptions $accountOptions,
        ParseHistoryOptions $historyOptions
    ): void {
        $startDate = $historyOptions->getStartDate();
        $this->logger->debug('[History start date: ' . ($startDate ? $startDate->format('Y/m/d H:i:s') : 'all') . ']');
        $startDate = isset($startDate) ? $startDate->format('U') : 0;
        $statement = $master->getStatement() ?? $master->createStatement();

        foreach ($this->history as $transaction) {
            $date = strtotime($transaction->TransactionDate);
            $description = $transaction->TransactionDescription;
            $miles = $transaction->Miles;
            $balance = $transaction->TransactionPaymentAmount;

            $result = [
                'Date'        => $date,
                'Description' => $description,
                'Miles'       => $miles,
                'Balance'     => $balance,
            ];

            if (
                $historyOptions->getStartDate()
                && $date < $historyOptions->getStartDate()->getTimestamp()
            ) {
                $this->logger->debug('SKIPPING ACTIVITY ROW: ' . var_export($result, true), ['pre' => true]);

                continue;
            }

            $this->logger->debug('ADDING ACTIVITY ROW: ' . var_export($result, true), ['pre' => true]);
            $statement->addActivityRow($result);
        }
    }

    private function parseReservationFlytap_2($response, $master)
    {
        $this->logger->notice(__METHOD__);
        //   link to fare total, taxes
        //   https://myb.flytap.com/bfm/rest/booking/pnrs/LKNXF9/fares/breakdown
        // $response = $this->http->JsonLog(null, 1, true, "data");

        if (isset($response->status) && $response->status == '400'
            && isset($response->ok) && $response->ok == true
            && isset($response->errors) && isset($response->errors[0]->desc)
            && (stripos($response->errors[0]->desc, 'NO MATCH FOR RECORD LOCATOR - NAME') !== false
                || stripos($response->errors[0]->desc, '100030 - Input data invalid') !== false
                || stripos($response->errors[0]->desc, '15623 - RESTRICTED ON OPERATING PNR') !== false
                || stripos($response->errors[0]->desc, 'Invalid last name format') !== false
                || stripos($response->errors[0]->desc, '284 - SECURED PNR') !== false)
        ) {
            if (
                stripos($response->errors[0]->desc, 'NO MATCH FOR RECORD LOCATOR - NAME') !== false
                /*
                && ArrayVal($this->AccountFields, 'Login')
                */
            ) {
                $this->notificationSender->sendNotification('refs #25513 tapportugal - check no match for record locator // IZ');
            }
            $this->logger->debug('Booking not found!');
            $this->errorRetrieve = 'Booking not found!';

            return true;
        }

        if (isset($response->status) && $response->status == '400'
            && isset($response->ok) && $response->ok == true
            && isset($response->errors) && isset($response->errors[0]->desc)
            && (stripos($response->errors[0]->desc, 'An error occurred while parsing') !== false
                || stripos($response->errors[0]->desc, '55 - IGNORE AND RE-ENTER') !== false)
        ) {
            return false; //false for break parsing
        }

        if (!isset($response->data->pnr)) {
            if (isset($response->status) && $response->status == '400') {
                $this->notificationSender->sendNotification("refs #25513 tapportugal - other format Json - parseReservationFlytap_2 // IZ");
            }

            return false;
        }

        $this->logger->error($response->errors[0]->desc ?? null);

        if (isset($response->status) && $response->status == '423'
            && (stripos($response->errors[0]->desc, 'Not present or wrong verification code') !== false)
        ) {
            $this->logger->error("Skip: " . $response->errors[0]->desc);

            return true; //false for break parsing
        }

        $f = $master->add()->flight();
        $conf = $response->data->pnr;
        $f->general()->confirmation($conf);

        foreach ($response->data->infoTicket->listTicket as $ticket) {
            $f->issued()->ticket($ticket->ticket, false);
        }
        $passengers = [];

        foreach ($response->data->infoPax->listPax as $pax) {
            $passengers[] = beautifulName($pax->name . ' ' . $pax->surname);
        }
        $f->general()->travellers($passengers, true);

        if (!empty($response->data->fare->flightPrice->totalPrice->currency)
            && (!empty($total->price) || !empty($response->data->fare->flightPrice->totalPoints))
        ) {
            $total = $response->data->fare->flightPrice->totalPrice;
            $f->price()
                ->total($total->price)
                ->cost($total->basePrice)
                ->tax($total->tax)
                ->currency($total->currency);

            if (!empty($response->data->fare->flightPrice->totalPoints->price)) {
                // not contains on the site
                //$f->price()->spentAwards($response->data->fare->flightPrice->totalPoints->price);
            }
        }

        if (isset($response->data->fare->listOutbound)
            && is_array($response->data->fare->listOutbound)
            && empty($response->data->fare->listOutbound)
        ) {
            $master->removeItinerary($f);
            $this->logger->debug('skip reservation. no info');

            return true;
        }
        $fields = ['listOutbound', 'inbound'];

        foreach ($fields as $field) {
            if (isset($response->data->fare->{$field})) {
                if (isset($response->data->fare->{$field}->idFlight)) {
                    $list = [$response->data->fare->{$field}];
                } else {
                    $list = $response->data->fare->{$field};
                }

                foreach ($list as $l) {
                    foreach ($l->listSegment as $segment) {
                        if ($segment->status[0] === '22') {
                            if (count($f->getSegments()) < 2) {
                                $this->notificationSender->sendNotification("refs #25513 tapportugal - check skipped segment // IZ");
                            }

                            continue; // skip reservation. duplicate(previous stops)
                        }

                        if (trim($segment->equipment) === 'TRN') {
                            // train segment
                            if (!isset($train)) {
                                $train = $master->add()->train();
                                $train->general()->confirmation($conf);

                                if (!empty($passengers)) {
                                    $train->general()->travellers($passengers, true);
                                }
                            }
                            $s = $train->addSegment();
                            $s->extra()->service($segment->operationCarrier);

                            if (!empty($segment->flightNumber)) {
                                $s->extra()->number($segment->flightNumber);
                            } else {
                                $s->extra()->noNumber();
                            }
                        } else {
                            // flight segment
                            $s = $f->addSegment();

                            if (isset($segment->flightNumber)) {
                                $s->airline()
                                    ->number($segment->flightNumber)
                                    ->name($segment->carrier)
                                    ->operator($segment->operationCarrier);
                            }

                            if (isset($segment->equipment)) {
                                $min = $segment->duration % 60;
                                $hours = round(($segment->duration - $min) / 60);
                                $duration = (($hours > 0) ? $hours . 'h ' : '') . $min . 'min';
                                $s->extra()
                                    ->aircraft(trim($segment->equipment), true)
                                    ->duration($duration);
                            }
                            $s->departure()
                                ->terminal($segment->departureTerminal, false, true);
                            $s->arrival()
                                ->terminal($segment->arrivalTerminal, false, true);
                        }

                        if (isset($segment->departureAirport)) {
                            $s->departure()
                                ->code($segment->departureAirport)
                                ->date(strtotime($segment->departureDate));
                        }

                        if (isset($segment->arrivalAirport)) {
                            $s->arrival()
                                ->code($segment->arrivalAirport)
                                ->date(strtotime($segment->arrivalDate));
                        }
                        $s->extra()
                            ->cabin($segment->cabin, false, true);

                        if (isset($segment->cabinMeal) && !empty(get_object_vars($segment->cabinMeal))) {
                            $this->notificationSender->sendNotification("refs #25513 tapportugal - not empty cabinMeal // IZ");
                        }

                        if (isset($segment->status) && is_object($segment->status)) {
                            if (in_array($segment->status->code, ['16', '21', '41', '31'])) {
                                $s->extra()
                                    ->status('cancelled')
                                    ->cancelled();
                            }

                            if (in_array($segment->status->code, ['80'])) {
                                $s->extra()
                                    ->status('flown');
                            }
                            // 17, 32 - one flight, 39, 46 - with stop (2+ flights), 21 - cancelled alone, 16 - cancelled with stops, 41 - cancelled 10,18 - round trip, 80 - flown, 31 - cancelled in pare with 21
                            if (!in_array($segment->status->code, ['10', '16', '17', '18', '19', '21', '39', '41', '46', '27', '24', '80', '31', '32', '34']) && !is_null($segment->status->code)) {
                                $this->notificationSender->sendNotification("refs #25513 tapportugal - check status // IZ");
                            }
                        }

                        // Seats
                        if (isset($response->data->ancillaries->seat->journey)) {
                            $seats = [];

                            foreach ($response->data->ancillaries->seat->journey as $journey) {
                                if ($l->idFlight == $journey->flightId && $segment->idInfoSegment == $journey->segmentId) {
                                    foreach ($journey->passengers as $passenger) {
                                        foreach ($passenger->ancillaryInfo as $info) {
                                            if (isset($info->seatCode)) {
                                                $seats[] = $info->seatCode;
                                            }
                                        }
                                    }
                                }
                            }

                            if (!empty($seats)) {
                                $s->extra()->seats(array_unique($seats));
                            }
                        }
                    }
                }
            }
        }
        $this->logger->debug('Parsed Itinerary:');
        $this->logger->debug(var_export($f->toArray(), true), ['pre' => true]);

        if (isset($train)) {
            $this->logger->debug('Parsed Itinerary (train):');
            $this->logger->debug(var_export($train->toArray(), true), ['pre' => true]);
        }

        return true;
    }
}
