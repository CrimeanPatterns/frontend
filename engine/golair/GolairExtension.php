<?php

namespace AwardWallet\Engine\golair;

use AwardWallet\Common\Parser\Util\PriceHelper;
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
use AwardWallet\Schema\Parser\Common;
use AwardWallet\Schema\Parser\Component\Master;
use AwardWallet\Schema\Parser\ParserTraits\TextTrait;
use Symfony\Component\DomCrawler\Crawler;

class GolairExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ParseItinerariesInterface
{
    use TextTrait;
    use \PriceTools;
    private $memberNumber;

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://www.smiles.com.br/group/guest/minha-conta';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        $el = $tab->evaluate('
            //input[@id="identifier"]
            | //span[contains(@class, "my-account") and contains(@class, "number") and contains(@class, "value")]
        ');

        return $el->getNodeName() === 'SPAN';
    }

    public function getLoginId(Tab $tab): string
    {
        return $tab->findText('//span[contains(@class, "my-account") and contains(@class, "number") and contains(@class, "value")]', FindTextOptions::new()->nonEmptyString());
    }

    public function logout(Tab $tab): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl("https://www.smiles.com.br/logout");
        $tab->evaluate('//div[@id="smls-hf-box-login"]');
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        $login = $tab->evaluate('//input[contains(@id, "identifier")]');
        $login->setValue($credentials->getLogin());
        $submit = $tab->evaluate('//main//button[contains(@data-testid, "submit")]');
        $submit->click();

        $result = $tab->evaluate('
            //p[@class="subtitle"]
            | //div[@class="input-error"]/label
            | //input[contains(@id, "password")]
            | //iframe[contains(@title, "reCAPTCHA")]
            | //div[contains(@class, "modal") and contains(@class, "show")]//p
        ');

        if ($result->getNodeName() == 'IFRAME') {
            $tab->showMessage(Tab::MESSAGE_RECAPTCHA);
            $result = $tab->evaluate('//input[contains(@id, "password")]', EvaluateOptions::new()->timeout(90)->allowNull(true));

            if (!isset($result)) {
                return LoginResult::captchaNotSolved();
            }
        }

        if ($result->getNodeName() == 'INPUT') {
            $password = $tab->evaluate('//input[contains(@id, "password")]');
            $password->setValue($credentials->getPassword());
            $tab->evaluate('//main//button[@type="button" and contains(@class, "animation")]')->click();
            $result = $tab->evaluate('
                //p[@class="subtitle"]
                | //div[@class="input-error"]/label
                | //iframe[contains(@title, "reCAPTCHA")]
                | //div[contains(@class, "modal") and contains(@class, "show")]//p
                | //span[contains(@class, "my-account") and contains(@class, "number") and contains(@class, "value")]
            ');
        }

        if ($result->getNodeName() == 'IFRAME') {
            $tab->showMessage(Tab::MESSAGE_RECAPTCHA);
            $result = $tab->evaluate('//span[contains(@class, "my-account") and contains(@class, "number") and contains(@class, "value")]', EvaluateOptions::new()->timeout(90)->allowNull(true));

            if (!isset($result)) {
                return LoginResult::captchaNotSolved();
            }
        }

        if ($result->getNodeName() == 'LABEL') {
            return LoginResult::invalidPassword($result->getInnerText());
        }

        if ($result->getNodeName() == 'P') {
            $error = $result->getInnerText();

            if (strstr($error, "Por favor, verifique CPF, número Smiles ou e-mail, e também sua senha antes de tentar mais uma vez.")) {
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

        try {
            $options = [
                'method'      => 'get',
                /*
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                */
            ];
            $json = $tab->fetch("https://www.smiles.com.br/group/guest/minha-conta?p_p_id=smileswidgetmydataportlet_WAR_smileswidgetmyaccountportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_resource_id=doWidgetMyData&p_p_cacheability=cacheLevelPage&p_p_col_id=column-2&p_p_col_count=2", $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $this->logger->info($json);
        $response = json_decode($json);
        $memberInfo = $response->userInfoHeaderVO->memberVO ?? null;
        // Balance - Saldo
        $statement->SetBalance($response->userInfoHeaderVO->widgetMyDataVO->availableMiles ?? null);
        // Name
        if (isset($memberInfo->firstName, $memberInfo->lastName)) {
            $statement->addProperty("Name", beautifulName($memberInfo->firstName . " " . $memberInfo->lastName));
        }
        // Número Smiles / Número Smiles
        $statement->addProperty("AccountNumber", $memberInfo->memberNumber ?? null);
        // Membro desde / Cliente desde
        $memberSince = $memberInfo->memberSince ?? null;

        if (isset($memberSince)) {
            $statement->addProperty("MemberSince", strtotime($memberSince));
        }
        // Category / Categoría
        $statement->addProperty("Category", $response->userInfoHeaderVO->widgetMyCategoryVO->currentTier ?? null);

        if (isset($response->userInfoHeaderVO->widgetMyCategoryVO->endDateCard) && strtotime(($response->userInfoHeaderVO->widgetMyCategoryVO->endDateCard))) {
            // Categoria / Categoría ... Válido até / Válida hasta
            $statement->addProperty("StatusExpiration", strtotime(($response->userInfoHeaderVO->widgetMyCategoryVO->endDateCard)));
        }
        // Para conquistar a categoria Prata    // refs #10704
        $milesClubTierUpgrade = $response->userInfoHeaderVO->tierUpgradeInfoVO->milesClubTierUpgrade ?? null;

        if ($milesClubTierUpgrade && $milesClubTierUpgrade > 0) {
            $statement->addProperty("MilesToNextLevel", $milesClubTierUpgrade);
        }
        // Para conquistar a categoria Prata    // refs #10704
        // milhas qualificáveis / millas calificables
        $statement->addProperty("QualifyingMiles", $response->userInfoHeaderVO->widgetMyCategoryVO->totalMiles ?? null);
        // trecho / tramos
        $statement->addProperty("Segments", $response->userInfoHeaderVO->widgetMyCategoryVO->totalSegments ?? null);

        // if exist error on the balance page then get info from header (provider bugfix)
        // AccountID: 4644834
        if (!$statement->getBalance()) {
            try {
                $options = [
                    'method'      => 'get',
                    /*
                    'cors'        => 'no-cors',
                    'credentials' => 'omit',
                    */
                ];
                $fetchResult = $tab->fetch("https://www.smiles.com.br/group/guest/minha-conta?p_p_id=smilesloginportlet_WAR_smilesloginportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_resource_id=renderLogin&p_p_cacheability=cacheLevelPage", $options);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());

                return;
            }

            if ($fetchResult->status !== 200) {
                return;
            }

            $page = $fetchResult->body;
            $crawler = new Crawler($page);
            // Balance - Você possui ... Milhas
            $balance = $crawler->filterXPath("(//div[contains(@class, 'dropdown-toggle')]//p[@class = 'miles'])[1]")->text();
            $balance = $this->findPreg($balance, '#([\d\.\,\-\s]+)#ims');

            if (isset($balance)) {
                $statement->setBalance($balance);
            }
            // Name
            $name = $crawler->filterXPath("(//div[contains(@class, 'dropdown-toggle')]//div[contains(@class, 'name')]/span)[1]")->text();

            if (isset($name)) {
                $statement->addProperty("Name", beautifulName($name));
            }

            // provider bug fix for broken accounts or failed previous request
            if (
                !$statement->getBalance()
                && $this->findPreg("/LoginPortletController.member/", $page)
            ) {
                // Balance - Saldo
                $balance = $this->findPreg("/'availableMiles':\s*'([^\']+)/", $page);

                if (isset($balance)) {
                    $statement->setBalance($balance);
                }
                // Name
                $firstName = $this->findPreg("/'name':\s*'([^\']+)/", $page);
                $lastName = $this->findPreg("/'lastName':\s*'([^\']+)/", $page);

                if (isset($firstName, $lastName)) {
                    $statement->addProperty("Name", beautifulName("{$firstName} {$lastName}"));
                }
                // Número Smiles / Número Smiles
                $number = $this->findPreg("/'memberNumber':\s*'([^\']+)/", $page);

                if (isset($number)) {
                    $statement->addProperty("AccountNumber", $number);
                }
                // Category / Categoría
                $category = $this->findPreg("/'category':\s*'([^\']+)/", $page);

                if (isset($category)) {
                    $statement->addProperty("Category", $category);
                }
                // Membro desde / Cliente desde
                $memberSince = $this->findPreg("/'memberSince':\s*'([^\']+)/", $page);

                if (isset($memberSince)) {
                    $statement->addProperty("MemberSince", date("d/m/Y", strtotime($memberSince)));
                }
            }
        }// if ($this->ErrorCode == ACCOUNT_ENGINE_ERROR)

        try {
            $options = [
                'method'      => 'post',
                /*
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                */
            ];
            $json = $tab->fetch("https://www.smiles.com.br/group/guest/minha-conta?p_p_id=smileswidgetmilestoexpireportlet_WAR_smileswidgetmyaccountportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_resource_id=doWidgetMilesToExpire&p_p_cacheability=cacheLevelPage&p_p_col_id=column-3&p_p_col_pos=1&p_p_col_count=14", $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return;
        }

        $this->logger->info($json);
        // Expiration Date
        $response = json_decode($json);
        $accrualList = $response->milesToExpireVO->widgetMilesToExpireList ?? [];

        foreach ($accrualList as $item) {
            if (isset($item->expiration, $item->points)) {
                $exp = $this->text->ModifyDateFormat($item->expiration);

                if ($exp = strtotime($exp, false)) {
                    //# Expiration Date
                    $statement->SetExpirationDate($exp);
                    //# Miles to expire
                    $statement->addProperty("MilesToExpire", $item->points);

                    break;
                }
            }
        }
    }

    public function parseItineraries(Tab $tab, Master $master, AccountOptions $options, ParseItinerariesOptions $parseItinerariesOptions): void
    {
        $this->logger->notice(__METHOD__);
        $tab->gotoUrl('https://www.smiles.com.br/group/guest/minha-conta');
        $tab->evaluate('//span[contains(@class, "my-account__number--value")]', EvaluateOptions::new()->allowNull(true)->timeout(10));
        $token = $this->findPreg("/MyFlightsController.token = '(\w+)';/", $tab->getHtml());

        if (empty($token)) {
            $token = $this->findPreg("/EasyTravelController.token = '(\w+)';/", $tab->getHtml());
        }

        if (empty($token)) {
            $tab->gotoUrl('https://www.smiles.com.br/group/guest/minha-conta');
            $tab->evaluate('//span[contains(@class, "my-account__number--value")]', EvaluateOptions::new()->allowNull(true)->timeout(10));
            $token = $this->findPreg("/MyFlightsController.token = '(\w+)';/", $tab->getHtml());

            if (empty($token)) {
                $token = $this->findPreg("/EasyTravelController.token = '(\w+)';/", $tab->getHtml());
            }

            if (empty($token)) {
                $this->logger->debug('could not find token');

                return;
            }
        }

        $headers = [
            'Accept'          => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Authorization'   => "Bearer {$token}",
            'Channel'         => 'Web',
            'Referer'         => 'https://www.smiles.com.br/',
            'Origin'          => 'https://www.smiles.com.br',
        ];

        $this->logger->info('Parse Future', ['Header' => 3]);
        $futureFlights = $this->getFlightsBrazil('https://member-flight-blue.smiles.com.br/member/flights?flightType=future&limit=8&offset=0', $headers, $tab);

        if ($futureFlights === []) {
            $futureFlights = $this->getFlightsBrazil('https://member-flight-green.smiles.com.br/member/flights?flightType=future&limit=8&offset=0', $headers, $tab);
        }

        if ($futureFlights === [] && !$parseItinerariesOptions->isParsePastItineraries()) {
            $master->setNoItineraries(true);
        }

        /*
        if (isset($futureFlights) && count($futureFlights) > 0) {
            $this->notificationSender->sendNotification('refs #25366 golair - need to check future flights // IZ');
        }
        */

        if (is_array($futureFlights)) {
            $this->logger->debug(sprintf('Total %s future reservations found', count($futureFlights)));

            if (count($futureFlights) > 20) {
                $this->notificationSender->sendNotification('refs #25366 golair - check futureFlights > 20 // IZ');
            }

            foreach ($futureFlights as $key => $item) {
                $this->parseItinerary($item, $master, $tab);
            }
        }

        if (!$parseItinerariesOptions->isParsePastItineraries()) {
            return;
        }

        $this->logger->info('Parse Past', ['Header' => 3]);
        $pastFlights = $this->getFlightsBrazil('https://member-flight-blue.smiles.com.br/member/flights?flightType=past&limit=20&offset=0', $headers, $tab);

        if ($futureFlights === [] && $pastFlights === [] && ($futureFlights === [] || $this->findPreg('/"errorMessage":"Ocorreu erro técnico."/u', $tab->getHtml()))) {
            $master->setNoItineraries(true);
        }

        /*
        if (isset($pastFlights) && count($pastFlights) > 0) {
            $this->notificationSender->sendNotification('refs #25366 golair - need to check past flights // IZ');
        }
        */

        if (is_array($pastFlights)) {
            $this->logger->debug(sprintf('Total %s past reservations found', count($pastFlights)));

            foreach ($pastFlights as $item) {
                $this->parseItinerary($item, $master, $tab);
            }
        }
    }

    private function parseItinerary(object $item, Master $master, Tab $tab): void
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

        $this->parsePriceBrazil($flight, $tab);
        $this->logger->info('Parsed Itinerary:');
        $this->logger->info(var_export($flight->toArray(), true), ['pre' => true]);
    }

    private function getFlightsBrazil(string $url, array $headers, Tab $tab): ?array
    {
        $this->logger->notice(__METHOD__);

        try {
            $options = [
                'method'      => 'get',
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                'headers'     => $headers,
            ];
            $json = $tab->fetch($url, $options)->body;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return null;
        }

        $this->logger->info($json);
        $response = json_decode($json);

        if (!isset($response->flightList) || !is_array($response->flightList)) {
            $this->notificationSender->sendNotification('refs #25366 golair - check getFlightsBrazil // IZ');
        }

        return $response->flightList ?? null;
    }

    private function parsePriceBrazil(Common\Flight $flight, Tab $tab): bool
    {
        $this->logger->notice(__METHOD__);
        $confNumbers = $flight->getConfirmationNumbers();
        $conf = $confNumbers[0][0] ?? null;

        if (!$conf) {
            return false;
        }

        try {
            $options = [
                'method'      => 'get',
                /*
                'cors'        => 'no-cors',
                'credentials' => 'omit',
                */
            ];
            $fetchResult = $tab->fetch("https://www.smiles.com.br/group/guest/minha-conta/meus-voos?p_p_id=smilesmyflightsportlet_WAR_smilesbookingportlet&p_p_lifecycle=2&p_p_state=normal&p_p_mode=view&p_p_resource_id=detailPayment&p_p_cacheability=cacheLevelPage&p_p_col_id=column-3&p_p_col_count=1&_smilesmyflightsportlet_WAR_smilesbookingportlet_currentURL=%2Fgroup%2Fguest%2Fminha-conta%2Fmeus-voos%3FurlCallback%3D%2Fgroup%2Fguest%2Fminha-conta%2Fmeus-voos&_smilesmyflightsportlet_WAR_smilesbookingportlet_recordLocator={$conf}", $options);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        if ($fetchResult->status !== 200) {
            $this->notificationSender->sendNotification('refs #25366 golair - check price request // IZ');

            return false;
        }

        $page = $fetchResult->body;
        $crawler = new Crawler($page);
        $totalStr = $crawler->filterXPath('//td[contains(text(), "TOTAL") or contains(text(), "Total")]/ancestor::tr[1]')->text();

        if ($totalStr) {
            // spent awards
            $miles = $this->findPreg('/(Mil.as\s*[\d.,]+)/', $totalStr);
            $flight->price()->spentAwards(preg_replace("/\s\s+/", " ", $miles), true);
            // total
            $total = $this->findPreg('/([\d.,]+)\s*$/', $totalStr) ?: '';
            $flight->price()->total(PriceHelper::cost($total, '.', ','), false, true);
            // currency
            $currency = $this->findPreg('/\s+([^\s]+)\s+[\d.,]+\s*$/', $totalStr) ?: '';
            $flight->price()->currency($this->currency($currency), false, true);
        }

        // tax
        $taxStr = $crawler->filterXPath('//td[contains(text(), "Taxa de Embarque") or contains(text(), "Tasas")]/ancestor::tr[1]/td[3]')->text();

        if ($taxStr) {
            $tax = $this->findPreg('/([\d.,]+)\s*$/', $taxStr) ?: '';

            if ($tax) {
                $flight->price()->tax(PriceHelper::cost($tax, '.', ','), false, true);
            }
        }
        // cost
        $costStr = $crawler->filterXPath('//td[contains(text(), "Bilhetes")]/ancestor::tr[1]/td[3]')->text();

        if ($costStr) {
            $cost = $this->findPreg('/([\d.,]+)\s*$/', $costStr);
            $flight->price()->cost(PriceHelper::cost($cost, '.', ','), false, true);
        }

        return true;
    }
}
