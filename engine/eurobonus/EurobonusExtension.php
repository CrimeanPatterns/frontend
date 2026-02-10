<?php

namespace AwardWallet\Engine\eurobonus;

use AwardWallet\Common\Parsing\Exception\NotAMemberException;
use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\ActiveTabInterface;
use AwardWallet\ExtensionWorker\ConfNoOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithConfNoInterface;
use AwardWallet\ExtensionWorker\LoginWithConfNoResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\Message;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesInterface;
use AwardWallet\ExtensionWorker\RetrieveByConfNoInterface;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class EurobonusExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ParseItinerariesInterface, LoginWithConfNoInterface, RetrieveByConfNoInterface, ActiveTabInterface
{
    use TextTrait;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.flysas.com/us-en/profile/';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $this->logger->info($tab->getUrl());
        $el = $tab->evaluate('//input[@id="username"] | //h3[normalize-space()="EuroBonus"]/preceding::button[1]/ancestor::div[1]');
        $tab->saveScreenshot();

        return $el->getNodeName() == "DIV";
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->evaluate('//h3[normalize-space()="EuroBonus"]/preceding::button[1]/ancestor::div[1]', EvaluateOptions::new()->nonEmptyString())->getInnerText();
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[@id="username"]');
        $login->setValue($credentials->getLogin());

        $password = $tab->evaluate('//input[@id="password"]');
        $password->setValue($credentials->getPassword());

        if ($tab->evaluate('//div[@id="ulp-auth0-v2-captcha"]', EvaluateOptions::new()->allowNull(true)->timeout(5))) {
            $tab->showMessage(Message::captcha('Continue'));
            $submitResult = $tab->evaluate('//h3[normalize-space()="EuroBonus"]/preceding::button[1]/ancestor::div[1] | //span[@id="error-element-password"]
            | //h3[contains(normalize-space(), "Join SAS EuroBonus")]/following::text()[normalize-space()][1][contains(normalize-space(), "Join SAS EuroBonus")]
            | //h1[normalize-space()="Welcome to the world of SAS"]',
                EvaluateOptions::new()->timeout(180)->allowNull(true));

            if (!$submitResult) {
                return LoginResult::captchaNotSolved();
            }

            //if show Select your country
            if ($submitResult->getNodeName() == 'H1') {
                $result = $this->waiter->waitFor(function () use ($tab) {
                    return $tab->evaluate('//button[contains(@class, "accept")]', EvaluateOptions::new()->allowNull(true)->timeout(5));
                });

                if ($result) {
                    $tab->evaluate('//button[contains(@class, "accept")]')->click();
                }

                sleep(1);

                $tab->gotoUrl('https://www.flysas.com/us-en/profile/');

                $submitResult = $tab->evaluate('//h3[normalize-space()="EuroBonus"]/preceding::button[1]/ancestor::div[1] | //span[@id="error-element-password"]
                | //h3[contains(normalize-space(), "Join SAS EuroBonus")]/following::text()[normalize-space()][1][contains(normalize-space(), "Join SAS EuroBonus")]',
                    EvaluateOptions::new()->timeout(65)->allowNull(true));
            }
        } else {
            $tab->evaluate('//button[@name="action" and contains(text(),"CONTINUE")]')->click();
            $submitResult = $tab->evaluate('//h3[normalize-space()="EuroBonus"]/preceding::button[1]/ancestor::div[1] 
              | //span[@id="error-element-password" 
              | //h3[contains(normalize-space(), "Join SAS EuroBonus")]/following::text()[normalize-space()][1][contains(normalize-space(), "Join SAS EuroBonus")]');
        }

        if (isset($submitResult) && $submitResult->getNodeName() == 'DIV') {
            return new LoginResult(true);
        } elseif (isset($submitResult)) {
            $error = $submitResult->getInnerText();

            if (strstr($error, "We couldn't find you using this login ID and password combination")
                || strstr($error, "Sorry, we couldn't find you with this login ID and password combination")
            ) {
                return new LoginResult(false, $error, null, ACCOUNT_INVALID_PASSWORD);
            }

            if (strstr($error, "Join SAS EuroBonus to earn points")
            ) {
                throw new NotAMemberException();
            }

            return new LoginResult(false, $error);
        }

        return new LoginResult(false);
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl('https://www.flysas.com/auth/logout');
        $tab->evaluate('//div[@class="sas-main-market-selector"] | //a[normalize-space()="Log in"]', EvaluateOptions::new()->timeout(65));
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $tab->saveHtml();
        $st = $master->createStatement();

        $cookies = $tab->getCookies();
        $this->logger->error($cookies['LOGIN_AUTH']);
        $token = $cookies['LOGIN_AUTH'] ?? null;
        $decodedToken = $this->jwtExtractPayload($token);
        $sessionID = $decodedToken->sessionId ?? null;

        $options = [
            'aw-no-cors'  => true, // TODO
            'cors'        => 'no-cors',
            'credentials' => 'omit',

            'method'  => 'get',
            'headers' => [
                'Authorization' => $token,
                'Accept'        => 'application/json',
            ],
        ];

        $json = $tab->fetch("https://api2.flysas.com/customer/profile/apim", $options)->body;
        $profile = json_decode($json);
        /*
        $options = [
            'cors'        => 'no-cors',
            'credentials' => 'omit',
            'method'      => 'post',
            'headers'     => [
                'Authorization' => $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json'
            ],
            'body' => json_encode([
                'id' => $sessionID
            ])
        ];

        $json = $tab->fetch('https://api2.flysas.com/customer/getProfile', $options)->body;
        $this->logger->info($json);
        $profile = json_decode($json);
        */

        // Balance - Bonus Points
        $st->setBalance($profile->account->mainPointsBalance);
        // Name
        $st->addProperty("Name", beautifulName($profile->customer->firstName . ' ' . $profile->customer->lastName));
        // EuroBonus number
        $st->addProperty("Number", $tab->findText('//h3[normalize-space()="EuroBonus"]/preceding::button[1]/ancestor::div[1]',
            FindTextOptions::new()->preg('/^[A-z\d]{10,}/')));
        // Current level
        $st->addProperty("Level", beautifulName($tab->findText('//h3[normalize-space()="EuroBonus"]/following-sibling::p[normalize-space()][1]')));
        // Member since
        $st->addProperty("MemberSince", strtotime($profile->account->enrollmentDate));

        // Qualifying points
        if (isset($profile->customer->attributes[5]->code) && $profile->customer->attributes[5]->code === 'C_B_PTS_THIS_Y') {
            $st->addProperty("QualifyingPoints", $profile->customer->attributes[5]->value);
        }

        // Qualifying flights
        if (isset($profile->customer->attributes[19]->code) && $profile->customer->attributes[19]->code === 'C_Q_QF_THIS_PRD') {
            $st->addProperty("QualifyingFlights", $profile->customer->attributes[19]->value);
        }

        // Qualifying period
        $qualifyingPeriodFromTemp = $qualifyingPeriodToTemp = null;

        if (isset($profile->customer->attributes[3]->code) && $profile->customer->attributes[3]->code === 'CT_Curr_QP_Start') {
            $qualifyingPeriodFromTemp = strtotime($profile->customer->attributes[3]->value);
        }

        if (isset($profile->customer->attributes[2]->code) && $profile->customer->attributes[2]->code === 'CT_Curr_QP_End') {
            $qualifyingPeriodToTemp = strtotime($profile->customer->attributes[2]->value);
        }

        $qualifyingPeriodFrom = date("M d, Y", $qualifyingPeriodFromTemp);
        $qualifyingPeriodTo = date("M d, Y", $qualifyingPeriodToTemp);

        $st->addProperty("QualifyingPeriod", $qualifyingPeriodFrom . " - " . $qualifyingPeriodTo);

        // Points required for upgrade
        if (isset($profile->customer->attributes[20]->code) && $profile->customer->attributes[20]->code === 'C_Q_QP_NEXT_LEV') {
            $st->addProperty("PointsRequiredForUpgrade", $profile->customer->attributes[20]->value);
        }

        /*if ($pointsUpdate) {
            $st->addProperty("PointsRequiredForUpgrade", $pointsUpdate);
        }*/

        // Flights required for upgrade
        if (isset($profile->customer->attributes[18]->code) && $profile->customer->attributes[18]->code === 'C_Q_QF_NEXT_LEV') {
            $st->addProperty("FlightsRequiredForUpgrade", $profile->customer->attributes[18]->value);
        }

        //Expiration Balance and Date
        $expDate = '';
        $expBalance = [];
        $pointsExpiration = $profile->pointsExpiration;

        foreach ($pointsExpiration as $pointExpiration) {
            if (empty($expDate)) {
                $expDate = $pointExpiration->expirationDate;
                $expBalance[] = $pointExpiration->points;
            } elseif ($expDate == $pointExpiration->expirationDate) {
                $expBalance[] = intval(str_replace(' ', '', $pointExpiration->points));
            } else {
                continue;
            }
        }

        if (!empty($expDate) && !empty($expBalance)) {
            $st->setExpirationDate(strtotime($expDate));
            $st->addProperty('ExpiringBalance', array_sum($expBalance));
        }
    }

    public function parseItineraries(Tab $tab, Master $master, AccountOptions $options, \AwardWallet\ExtensionWorker\ParseItinerariesOptions $parseItinerariesOptions): void
    {
        $cookies = $tab->getCookies();
        $token = $cookies['LOGIN_AUTH'] ?? null;
        $decodedToken = $this->jwtExtractPayload($token);
        $customerSessionID = $decodedToken->customerSessionId ?? null;

        $options = [
            'aw-no-cors'  => true, // TODO
            'cors'        => 'no-cors',
            'credentials' => 'omit',

            'method'      => 'get',
            'headers'     => [
                'Authorization'   => $token,
                'Accept'          => 'application/json, text/plain, */*',
                'Content-Type'    => 'application/json',
                'Referer'         => null,
                'Origin'          => 'https://www.flysas.com',
                'Accept-Language' => 'en_us',
            ],
        ];

        $json = $tab->fetch("https://api2.flysas.com/reservation/reservations?context=RES&customerID={$customerSessionID}", $options)->body;
        $this->logger->info($json);
        $reservationsData = json_decode($json);
        $reservations = $reservationsData->reservations ?? [];
        $countItineraries = count($reservations);
        $this->logger->debug("Total {$countItineraries} itineraries were found");

        if (empty($reservations) && ($msg = $this->findPreg("/\"code\":\"3011203\",\"description\":\"Unfortunately, we can't get your bookings at the moment. Please try again./", $json))) {
            // retry not work
            $this->logger->error($msg);

            return;
        }

        foreach ($reservations as $reservation) {
            $airlineBookingReference = $reservation->airlineBookingReference ?? null;
            $status = $reservation->status ?? null;

            if (!in_array($status, ['Confirmed', 'Waitlisted', 'Cancelled', 'Space Available', 'SpaceAvailable'])) {
                $this->notificationSender->sendNotification("refs #24888 eurobonus - new itinerary status was found: {$status} // IZ");
            }
            $this->logger->info("Parse Itinerary #{$airlineBookingReference}", ['Header' => 3]);

            if ($status == 'Cancelled') {
                $f = $master->add()->flight();
                $f->general()->confirmation($airlineBookingReference);
                $f->general()->cancelled();

                continue;
            }
            $fields = [
                'ConfNo'   => $reservation->airlineBookingReference ?? null,
                'LastName' => $this->findPreg('/lastName=(.*)/', $reservation->links[0]->href ?? ''),
            ];
            $result[] = $this->parseItinerary($tab, $master, $fields);
        }
        // no its
        if (empty($result) && $this->findPreg("/\"code\":\"3011204\",\"description\":\"You don't seem to have any active bookings. You could always add a booking to your profile at any time./", $json)) {
            $master->setNoItineraries(true);
        }
    }

    public function isActiveTab(AccountOptions $options): bool
    {
        return true;
    }

    public function getLoginWithConfNoStartingUrl(array $confNoFields, ConfNoOptions $options): string
    {
        return 'https://www.flysas.com/us-en/managemybooking/';
    }

    public function loginWithConfNo(Tab $tab, array $confNoFields, ConfNoOptions $options): LoginWithConfNoResult
    {
        $tab->evaluate('//input[@name="bookingReference"]')->setValue($confNoFields['ConfNo']);
        $tab->evaluate('//input[@name="lastName"]')->setValue($confNoFields['LastName']);
        $tab->evaluate('//button[@type="submit" and ./p[contains(text(),"Search")]]')->click();
        $loginResult = $tab->evaluate('//p[contains(text(), "Sorry, something went wrong when we were processing your request. Please try again.")]
        | //p[contains(text(),"Booking Ref.")]');

        if ($loginResult) {
            $error = $loginResult->getInnerText();

            if (stristr($error, "Sorry, something went wrong when we were processing your request. Please try again.")) {
                return LoginWithConfNoResult::error($error);
            }
        }

        $tab->saveScreenshot();
        $tab->saveHtml();

        return LoginWithConfNoResult::success();
    }

    public function retrieveByConfNo(Tab $tab, Master $master, array $fields, ConfNoOptions $options): void
    {
        $this->parseItinerary($tab, $master, $fields);
    }

    private function parseItinerary(Tab $tab, Master $master, array $fields)
    {
        $this->logger->notice(__METHOD__);
        $result = [];

        $options = [
            'aw-no-cors'  => true, // TODO
            'cors'        => 'no-cors',
            'credentials' => 'omit',

            'method'      => 'get',
            'headers'     => [
                'Accept'          => 'application/json, text/plain, */*',
                'Content-Type'    => 'application/json',
                'Referer'         => null,
                'Origin'          => 'https://www.flysas.com',
                'Accept-Language' => 'en_us',
            ],
        ];

        $json = $tab->fetch("https://api2.flysas.com/reservation-service/reservation?bookingReference={$fields['ConfNo']}&names={$fields['LastName']}", $options)->body;
        $this->logger->info($json);
        $response = json_decode($json);

        $legacy = $response->data->LEGACY ?? null;

        if (!isset($legacy)) {
            $this->notificationSender->sendNotification("refs #24888 eurobonus - empty reservation // IZ");

            /*
            $this->logger->error($response->responseMessages[0]['description'] ?? null);

            if (
                isset($response['responseMessages'][0]['description'])
                && $response['responseMessages'][0]['description'] == 'Sorry, something went wrong when we were processing your request. Please try again.'
            ) {
                $this->logger->notice("grab info from main json");
                $reservation = $reservationInfo;
            }

            if (!isset($reservation)) {
                return $result;
            }
            */

            return $result;
        }

        $f = $master->add()->flight();
        $f->general()->confirmation($legacy->airlineBookingReference ?? null);
        $bookingClasses = [];
        $status = $legacy->status ?? null;

        if (isset($status)) {
            foreach ($status->statusDetails ?? [] as $key => $statusDetail) {
                foreach ($statusDetail->reservationStatus ?? [] as $keyReservationStatus => $reservationStatus) {
                    if ($reservationStatus->HK->bookingClass ?? null) {
                        $bookingClasses[$keyReservationStatus][] = $reservationStatus->HK->bookingClass;
                    }
                }
            }
            $f->general()->status($status->reservationStatus);
        }
        $this->logger->debug('bookingClasses:');
        $this->logger->debug(var_export($bookingClasses, true));

        // Passengers
        $passengers = $legacy->passengersArray ?? [];
        $accountNumbers = [];

        foreach ($passengers as $passenger) {
            $f->general()->traveller(beautifulName($passenger->firstName . " " . $passenger->lastName));

            if (isset($passenger->engagements->euroBonus[0]->id)) {
                $accountNumbers[] = $passenger->engagements->euroBonus[0]->id;
            }
        }

        if (!empty($accountNumbers)) {
            $f->program()->accounts(array_unique($accountNumbers), false);
        }
        // TicketNumbers
        $tickets = [];
        $documents = $legacy->documentInformation ?? [];

        foreach ($documents as $document) {
            foreach ($document->flights ?? [] as $flight) {
                foreach ($flight->documentNumber ?? [] as $ticket) {
                    $tickets[] = $ticket;
                }
            }
        }
        $this->logger->debug(var_export($tickets, true));
        $tickets = array_unique(array_filter($tickets, function ($s) {
            return preg_match("/^\d{3}-\d+$/", $s);
        }));

        if (!empty($tickets)) {
            $f->issued()->tickets($tickets, false);
        }

        // Air trip segment

        $ancillaryProducts = $legacy->ancillaryProducts ?? [];

        if (empty($ancillaryProducts)) {
            $ancillaryProducts = $reservation->ancillaries ?? [];
        }

        foreach ($ancillaryProducts as $product) {
            switch ($product->name) {
                case 'meal':
                    break;

                case 'seat':
                    $seats = [];
                    $connections = $product->connections ?? [];

                    foreach ($connections as $connection) {
                        foreach ($connection->segments ?? [] as $segment) {
                            foreach ($segment->passengers ?? [] as $passenger) {
                                $number = $passenger->allowance->number ?? null;

                                if ($this->findPreg("/^\d+[A-Z]$/", $number)) {
                                    $seats[$segment->id][] = $number;
                                }
                            }
                        }
                    }
                    $this->logger->debug('seats:');
                    $this->logger->debug(var_export($seats, true));

                    break;
            }
        }

        $connections = $legacy->connectionsArray ?? [];
        $this->logger->debug('Total ' . count($connections) . ' legs were found');

        foreach ($connections as $connection) {
            $segments = $connection->segments ?? [];

            /*foreach ($connection as $bound) {
                $status = $bound->status ?? null;

                if (!in_array($status, ['Active', 'Flown'])) {
                    $this->logger->error('new status out/in-Bound leg');

                    break 2;
                }
                $segments = array_merge($segments, $bound->segments ?? []);
                $this->notificationSender->sendNotification('check array_merge segments  // MI');
            }*/

            $this->logger->debug('Total ' . count($segments) . ' segments were found');

            foreach ($segments as $segment) {
                $s = $f->addSegment();
                $s->airline()->number($segment->operatingCarrier->flightNumber ?? $segment->marketingCarrier->flightNumber ?? null);
                $s->airline()->name($segment->operatingCarrier->code ?? $segment->marketingCarrier->code ?? null);

                $s->airline()->operator($segment->operatingCarrier->name, true);
                $s->extra()->aircraft($segment->aircraft->name ?? null, false, true);
                $s->extra()->duration($this->findPreg("/^PT(\d.+)$/", $segment->duration));

                $s->departure()->date2($segment->scheduledDepartureLocal);
                $s->arrival()->date2($segment->scheduledArrivalLocal);

                $s->departure()->code($segment->departure->airportCode ?? null);
                $s->arrival()->code($segment->arrival->airportCode ?? null);

                $s->departure()->terminal($segment->departure->terminal ?? null, false, true);
                $s->arrival()->terminal($segment->arrival->terminal ?? null, false, true);

                $segmentKey = $segment->segmentKey ?? null;

                if (isset($seats[$segmentKey])) {
                    $s->extra()->seats(array_unique($seats[$segmentKey]));
                }

                if (empty($s->getBookingCode()) && isset($bookingClasses[$segmentKey])) {
                    $s->extra()->bookingCode(implode('|', array_unique($bookingClasses[$segmentKey])));
                }

                if ($s->getDepCode() === $s->getArrCode()) {
                    $master->removeItinerary($f);
                    $this->logger->error("Skipping invalid segment ({$s->getAirlineName()}{$s->getFlightNumber()}) with the same dep / arr codes and dates");
                }
            }
        }

        $this->logger->debug('Parsed itinerary:');
        $this->logger->debug(var_export($result, true), ['pre' => true]);

        return $result;
    }

    private function convertBase64UrlToBase64(string $input): string
    {
        $remainder = \strlen($input) % 4;

        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= \str_repeat('=', $padlen);
        }

        return \strtr($input, '-_', '+/');
    }

    private function jwtExtractPayload(string $jwt)
    {
        $this->logger->debug('DECODING JWT: ' . $jwt);
        $bodyb64 = explode('.', $jwt)[1];
        $this->logger->debug('JWT BODY: ' . $bodyb64);
        $bodyb64Prepared = $this->convertBase64UrlToBase64($bodyb64);
        $this->logger->debug('JWT BODY B64 PREPARED: ' . $bodyb64Prepared);
        $payloadRaw = base64_decode($bodyb64Prepared);
        $this->logger->debug('JWT PAYLOAD RAW: ' . $bodyb64Prepared);

        return json_decode($payloadRaw);
    }
}
