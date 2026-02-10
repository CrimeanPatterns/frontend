<?php

namespace AwardWallet\Engine\golair;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\EvaluateOptions;
use AwardWallet\ExtensionWorker\FindTextOptions;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\ParseInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesInterface;
use AwardWallet\ExtensionWorker\ParseItinerariesOptions;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;

class GolairExtensionArgentina extends AbstractParser implements LoginWithIdInterface, ParseInterface, ParseItinerariesInterface
{
    use TextTrait;
    private $memberNumber;
    public function getStartingUrl(AccountOptions $options): string
    {
        $this->county = $options->login2;
        $this->memberNumber = $options->login;
        return 'https://www.smiles.com.ar/myaccount';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('//span[contains(@class, "member-number-text")] | //input[@id="dni"]');
        return $el->getNodeName() === 'SPAN';
    }

    public function getLoginId(Tab $tab): string
    {
        // $tab->gotoUrl('https://www.smiles.com.ar/myaccount');
        return $tab->findText('//span[contains(@class, "member-number-text")]', FindTextOptions::new()->nonEmptyString());
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl("https://www.smiles.com.ar/logout");
        $tab->evaluate('//button[contains(@class, "btn-login")]');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[contains(@id, "dni")]');
        $login->setValue($credentials->getLogin());
        $password = $tab->evaluate('//input[contains(@id, "password")]');
        $password->setValue($credentials->getPassword());
        $submit = $tab->evaluate('//button[contains(@class, "LoaderButton") and @type="submit"]');
        $submit->click();
        $result = $tab->evaluate('
            //div[contains(@class, "alert-danger")]
            | //span[contains(@class, "member-number-text")]
            | //iframe[@title="reCAPTCHA"]
        ');

        if ($result->getNodeName() == 'IFRAME') {
            $tab->showMessage(Tab::MESSAGE_RECAPTCHA);
            $result = $tab->evaluate('
                //div[contains(@class, "alert-danger")]
                | //span[contains(@class, "member-number-text")]
            ', EvaluateOptions::new()->timeout(120)->allowNull(true));

            if (!isset($result)) {
                return LoginResult::captchaNotSolved();
            }
        }

        if ($result->getNodeName() == 'DIV') {
            $error = $result->getInnerText();

            if (strstr($error, "Usuario no encontrado, o contraseña inválida.")) {
                return LoginResult::invalidPassword($error);
            }

            return new LoginResult(false, $error);
        }

        if ($result->getNodeName() == 'SPAN') {
            return LoginResult::success();
        }

        return new LoginResult(false);        
    }

    public function parse(Tab $tab, Master $master, AccountOptions $accountOptions): void
    {
        $this->logger->notice(__METHOD__);
        $statement = $master->createStatement();
        $token = $tab->getFromSessionStorage('accessToken');
        $tab->logPageState();

        if (!isset($token)) {
            return;
        }

        $data = [
            'memberNumber' => $this->memberNumber,
            'token'        => $token,
        ];

        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'Content-Type'  => 'application/json;charset=UTF-8',
            'Authorization' => 'Bearer ' . $token,
            'region'        => 'ARGENTINA',
            'Channel'       => 'Web',
            'Language'      => 'es-ES',
        ];

        try {
            $options = [
                'method'      => 'post',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
                'body'        => json_encode($data),
            ];
            $json = $tab->fetch("https://api.smiles.com.br/smiles-bus/MemberRESTV1/GetMember", $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $this->logger->info($json);
        $response = json_decode($json);
        // Balance - Saldo
        $statement->SetBalance($response->member->availableMiles ?? null);
        // Name
        if (isset($response->member->firstName, $response->member->lastName)) {
            $statement->addProperty("Name", beautifulName("{$response->member->firstName} {$response->member->lastName}"));
        }
        // AccountNumber - Número Smiles
        $statement->addProperty("AccountNumber", $response->member->memberNumber ?? null);
        // MemberSince - Cliente desde
        $memberSince = $response->member->memberSince ?? null;

        if (isset($memberSince)) {
            $statement->addProperty("MemberSince", strtotime($memberSince));
        }

        // Expiration Date
        if (isset($response->member->milesNextExpirationDate, $response->member->milesToExpire) && $response->member->milesToExpire > 0) {
            if ($exp = $this->findPreg('/(\d{4}-\d+-\d+)T/', $response->member->milesNextExpirationDate)) {
                $statement->SetExpirationDate(strtotime($exp, false));
                // Miles to expire
                $statement->addProperty("MilesToExpire", number_format($response->member->milesToExpire, 0, ',', '.'));
            }
        }

        try {
            $options = [
                'method'      => 'get',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
            ];
            $json = $tab->fetch("https://api.smiles.com.br/api/members/tier", $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $this->logger->info($json);
        $response = json_decode($json);
        // QualifyingMiles - millas calificables*
        $statement->addProperty("QualifyingMiles", number_format($response->tierUpgradeInfo->totalMilesClub ?? null, 0, ',', '.'));
        // Segments - tramos volados*
        $statement->addProperty("Segments", $response->tierUpgradeInfo->totalSegments ?? null);

        // Category - Categoría
        if (isset($response->tierUpgradeInfo->currentTier)) {
            $tier = strtolower($response->tierUpgradeInfo->currentTier);

            if ($tier == 'smiles') {
                $tier = 'Member';
            }

            if ($tier == 'prata') {
                $tier = 'Silver';
            }

            if ($tier == 'ouro') {
                $tier = 'Gold';
            }

            if ($tier == 'diamante') {
                $tier = 'Diamond';
            }
            $statement->addProperty("Category", $tier);
        }
        // MilesToNextLevel
        if (isset($response->tierUpgradeInfo->milesClubTierUpgrade) && $response->tierUpgradeInfo->milesClubTierUpgrade > 0) {
            $statement->addProperty("MilesToNextLevel", number_format($response->tierUpgradeInfo->milesClubTierUpgrade, 0, ',', '.'));
        }

        try {
            $options = [
                'method'      => 'post',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
                'body'        => json_encode($data + ['status' => 'ALL']),
            ];
            $json = $tab->fetch("https://api.smiles.com.br/smiles-bus/MemberRESTV1/SearchTier", $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $this->logger->info($json);
        $response = json_decode($json);

        if (!$response && !$statement->getBalance()) {
            $this->notificationSender->sendNotification("refs #25366 - Argentina. Exp date not found // IZ");
        }

        if (!isset($response->tierList)) {
            return;
        }
        // StatusExpiration - Válida hasta
        $minDate = strtotime('01/01/3018');

        foreach ($response->tierList->tier as $tier) {
            $expStatus = strtotime($tier->endDate, false);

            if ($expStatus && $expStatus < $minDate && $expStatus > strtotime('now')) {
                $statement->addProperty("StatusExpiration", $expStatus);

                break;
            }
        }        
    }

    public function parseItineraries(Tab $tab, Master $master, AccountOptions $options, ParseItinerariesOptions $parseItinerariesOptions): void
    {
        $this->logger->notice(__METHOD__);

        $this->logger->notice(__METHOD__);
        $statement = $master->createStatement();
        $token = $tab->getFromSessionStorage('accessToken');
        $tab->logPageState();

        if (!isset($token)) {
            return;
        }

        $headers = [
            'Accept'        => 'application/json, text/plain, */*',
            'Content-Type'  => 'application/json;charset=UTF-8',
            'Authorization' => 'Bearer ' . $token,
            'region'        => 'ARGENTINA',
            'Channel'       => 'Web',
            'Language'      => 'es-ES',
        ];

        try {
            $options = [
                'method'      => 'get',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
            ];
            $json = $tab->fetch("https://api.smiles.com.br/api/v2/members/flights?memberNumber={$this->memberNumber}&getFutureFlights=true&getPastFlights=true&getLoanFlights=true&getReservationFlights=true", $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $this->logger->info($json);
        $response = json_decode($json);

        if (!empty($response->reservationFlightList)) {
            $this->notificationSender->sendNotification('golair - refs #17986. reservationFlightList itineraries were found // IZ');
        }

        $notUpcoming = $this->findPreg('/,"futureFlightList":\[\],/', $tab->getHtml()) && $this->findPreg('/,"loanFlightList":\[\],/', $tab->getHtml());

        if (!empty($response->futureFlightList)) {
            $this->logger->debug("Total " . count($response->futureFlightList) . " future reservations found");

            foreach ($response->futureFlightList as $item) {
                $this->parseItinerary($item, $master);
            }
        }

        if (!empty($response->loanFlightList)) {
            $this->logger->debug("Total " . count($response->loanFlightList) . " loan reservations found");

            foreach ($response->loanFlightList as $item) {
                $this->parseItinerary($item, $master);
            }
        }

        if ($parseItinerariesOptions->isParsePastItineraries()) {
            $this->logger->info("Past Itineraries", ['Header' => 2]);

            if (!empty($response->pastFlightList)) {
                $this->logger->debug("Total " . count($response->pastFlightList) . " past reservations found");

                foreach ($response->pastFlightList as $item) {
                    $this->parseItinerary($item, $master);
                }
            } elseif ($notUpcoming && $this->findPreg('/,"pastFlightList":\[\],/', $tab->getHtml())) {
                $master->setNoItineraries(true);
            }
        } else {
            if ($notUpcoming) {
                $master->setNoItineraries(true);
            }
        }
    }

    private function parseItinerary(Object $item, Master $master): void
    {
        $this->logger->notice(__METHOD__);

        if (empty($item->flight->chosenFlightSegmentList)) {
            return;
        }

        $flight = $master->createFlight();

        if (isset($item->bookingStatus)) {
            $bookingStatus = $item->bookingStatus;

            if (in_array($bookingStatus, ['CONFIRMED', 'SEGMENT_CONFIRMED_AFTER_SCHEDULE_CHANGE', 'UNABLE_FLIGHT_DOES_NOT_OPERATE'])) {
                $flight->setStatus('Confirmed');
            } elseif (in_array($bookingStatus, ['CANCELLED'])) {
                $flight->setStatus('Cancelled');
                $flight->setCancelled(true);
            }
        }

        $conf = $ticketNumbers = [];
        $segments = $item->flight->chosenFlightSegmentList;
        $this->logger->debug("Total " . count($segments) . " segments found");

        foreach ($segments as $segment) {
            // Travellers ticketNumber
            if (isset($segment->passengerList)) {
                $ticketNumbers = array_merge($ticketNumbers, array_column($segment->passengerList, 'ticketNumber'));
            }
            $conf[] = $segment->recordLocator;
            $this->logger->debug("Total " . count($segment->chosenFlight->legList) . " stops found");

            foreach ($segment->chosenFlight->legList as $leg) {
                if (isset($leg->flightNumber) && $leg->flightNumber == "") {
                    $this->logger->notice('Skip segment: not flightNumber');

                    continue;
                }
                $seg = $flight->addSegment();
                // AirlineName
                $seg->setAirlineName($leg->marketingAirline->code ?? null);
                $seg->setFlightNumber($leg->flightNumber);

                if (isset($leg->operationAirline->code)) {
                    $seg->setOperatedBy($leg->operationAirline->code);
                }

                if (isset($leg->equipment)) {
                    $seg->setAircraft($leg->equipment, true);
                }

                if (isset($leg->cabin)) {
                    $seg->setCabin(beautifulName($leg->cabin));
                }

                // Departure
                $seg->setDepCode($leg->departure->airport->code);

                if (isset($leg->arrival->departure->name, $leg->arrival->departure->city)) {
                    $seg->setDepName($leg->departure->airport->name . ', ' . $leg->departure->airport->city);
                }

                $seg->setDepDate(strtotime(str_replace('T', ' ', $leg->departure->date), false));
                // Arrival
                $seg->setArrCode($leg->arrival->airport->code);

                if (isset($leg->arrival->airport->name, $leg->arrival->airport->city)) {
                    $seg->setArrName($leg->arrival->airport->name . ', ' . $leg->arrival->airport->city);
                }

                if (isset($leg->departure->date) && !isset($leg->arrival->date)) {
                    $seg->setNoArrDate(true);
                } else {
                    $seg->setArrDate(strtotime(str_replace('T', ' ', $leg->arrival->date), false));
                }
            }

            if (isset($seg) && count($segment->chosenFlight->legList) == 1) {
                // Duration
                if (isset($segment->chosenFlight->duration->hours, $segment->chosenFlight->duration->minutes)) {
                    $duration = $segment->chosenFlight->duration->hours . 'h' . $segment->chosenFlight->duration->minutes;
                } elseif (!isset($segment->chosenFlight->duration->hours) && isset($segment->chosenFlight->duration->minutes)) {
                    $duration = $segment->chosenFlight->duration->minutes . 'm';
                }

                if (isset($duration)) {
                    $seg->setDuration($duration);
                }
            }
        }

        if (empty($flight->getSegments())) {
            $this->logger->error('Skip it: not flightNumber');
            $master->removeItinerary($flight);

            return;
        }

        // TicketNumbers
        $flight->setTicketNumbers(array_unique($ticketNumbers), false);
        // Travellers
        $travellers = [];

        if (isset($item->flight->passengerList)) {
            foreach ($item->flight->passengerList as $traveller) {
                if (isset($traveller->firstName)) {
                    $travellers[] = beautifulName("{$traveller->firstName} {$traveller->lastName}");
                }
            }
        }

        if ($travellers) {
            $flight->setTravellers($travellers);
        }

        // ConfirmationNumber
        if (!empty($conf)) {
            foreach ($conf as $locator) {
                $flight->addConfirmationNumber($locator, 'Localizador');
            }
        }

        $moneyCost = $item->moneyCost ?? null;

        if ($moneyCost) {
            $moneyCost = (float) str_replace(" ", '', $moneyCost);

            if ($moneyCost) {
                $flight->price()->total($moneyCost);
                $flight->price()->currency('ARS');
            }
        }

        $flight->price()->spentAwards($item->loanedMiles ?? null, false, true);
        $this->logger->info('Parsed Itinerary:');
        $this->logger->info(var_export($flight->toArray(), true), ['pre' => true]);
    }
}