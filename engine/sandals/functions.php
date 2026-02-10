<?php

class TAccountCheckerSandals extends TAccountChecker
{
    private const REWARDS_PAGE_URL = 'https://accountscms.sandals.com/api/guests-service/v2/guest-data';
    /**
     * @var CaptchaRecognizer
     */
    private $recognizer;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
    }

    public function IsLoggedIn()
    {
        if ($this->loginSuccessful()) {
            return true;
        }

        return false;
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL("https://www.sandalsselect.com/");

        if ($this->http->Response['code'] !== 200) {
            return $this->checkErrors();
        }

        $data = [
            'recaptcha'    => '',
            'staySignedIn' => 'true',
            'email'        => $this->AccountFields['Login'],
            'username'     => $this->AccountFields['Login'],
            'password'     => $this->AccountFields['Pass'],
        ];
        $headers = [
            "Accept"       => "application/json, text/plain, */*",
            "Content-Type" => "application/json",
        ];
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://accountscms.sandals.com/api/guests-service/v2/sign-in', json_encode($data), $headers);
        $this->http->RetryCount = 2;

        return true;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if (isset($response->content->message) && $response->content->message == "User logged in succesfully") {
            $this->captchaReporting($this->recognizer);

            return $this->loginSuccessful();
        }

        $message = $response->content->errors[0]->message ?? null;

        if ($message) {
            $this->logger->error("[Error]: {$message}");

            switch ($message) {
                case "internal service error - user does not exist":
                case "internal service error - invalid credentials":
                    $this->captchaReporting($this->recognizer);

                    throw new CheckException("Invalid user credentials. The email or password you entered is incorrect.", ACCOUNT_INVALID_PASSWORD);

                case "internal service error - password with outdated policy":
                    $this->captchaReporting($this->recognizer);

                    throw new CheckException("Due to system & security updates, our password requirements have changed and will require you to reset your password to login to your account.", ACCOUNT_INVALID_PASSWORD);
//                case "Error validating ReCaptcha!":
//                    $this->captchaReporting($this->recognizer, false);
//
//                    throw new CheckRetryNeededException(3, 0, self::CAPTCHA_ERROR_MSG);

                default:
                    $this->DebugInfo = $message;

                    return false;
            }
        }

        return $this->checkErrors();
    }

    public function Parse()
    {
        $response = $this->http->JsonLog(null, 0);
        $trips = $response->content->data->trips ?? [];

        if (!empty($trips)) {
            $this->sendNotification("itineraries were found");
        }

        $points = $response->content->data->points ?? null;
        // Balance - Available Points Balance
        $this->SetBalance($points->points_total_balance ?? null);
        // Member ID
        $this->SetProperty('Membership', $points->account_no ?? null);
        // Total Paid Nights
        $this->SetProperty('TotalPaidNights', $points->nights);

        $user = $response->content->data->user ?? null;
        // Name
        $this->SetProperty('Name', beautifulName(($user->primary_member->first_name ?? null)." ".($user->primary_member->last_name ?? null)));
        // Level Status
        $this->SetProperty('Level', $user->member_level ?? null);
        // Member since
        $memberSinceText = $user->join_date ?? null;

        if ($memberSinceText) {
            $this->SetProperty('MemberSince', strtotime($memberSinceText));
        }
    }

    public function ParseItineraries()
    {
        $this->http->GetURL("https://www.sandalsselect.com/load-stays/");
        $response = $this->http->JsonLog();

        $pastItineraries = $response->data->pastStays ?? null;
        $upcomingItineraries = $response->data->futureStays ?? null;

        $upcomingItinerariesIsPresent = $upcomingItineraries !== null && !empty($response->data->futureStays);
        $pastItinerariesIsPresent = $pastItineraries !== null && !empty($response->data->pastStays);

        $this->logger->debug('Upcoming itineraries is present: '.(int)$upcomingItinerariesIsPresent);
        $this->logger->debug('Previous itineraries is present: '.(int)$pastItinerariesIsPresent);

        // check for the no its
        $seemsNoIts = !$upcomingItinerariesIsPresent && !$pastItinerariesIsPresent;
        $this->logger->info('Seems no itineraries: '.(int)$seemsNoIts);
        $this->logger->info('ParsePastIts: '.(int)$this->ParsePastIts);

        if (!$upcomingItinerariesIsPresent && !$this->ParsePastIts) {
            $this->itinerariesMaster->setNoItineraries(true);

            return [];
        }

        if ($seemsNoIts && !$this->ParsePastIts) {
            $this->itinerariesMaster->setNoItineraries(true);

            return [];
        }

        if ($seemsNoIts && $this->ParsePastIts && !$pastItinerariesIsPresent) {
            $this->itinerariesMaster->setNoItineraries(true);

            return [];
        }

        if ($upcomingItinerariesIsPresent) {
            foreach ($upcomingItineraries as $node) {
                $this->parseFutureItinerary($node);
            }
        }

        // if ($pastItinerariesIsPresent && $this->ParsePastIts) {
        if ($pastItinerariesIsPresent) {
            foreach ($pastItineraries as $node) {
                $this->parsePastItinerary($node);
            }
        }

        return [];
    }

    private function loginSuccessful()
    {
        $this->logger->notice(__METHOD__);
        $this->http->RetryCount = 0;
        $this->http->GetURL(self::REWARDS_PAGE_URL, [], 20);
        $this->http->RetryCount = 2;

        $response = $this->http->JsonLog(null, 5);
        $username = $response->content->data->user->username ?? null;
        $this->logger->debug("[Username]: {$username}");

        if ($username && strtolower($username) == strtolower($this->AccountFields['Login'])) {
            return true;
        }

        return false;
    }

    private function checkErrors()
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode('//h1[contains(text(), "Service Unavailable - DNS failure")]')) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    private function parseReCaptcha($key)
    {
        $this->logger->notice(__METHOD__);

        if (!$key) {
            return false;
        }

        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "pageurl" => "https://www.sandalsselect.com/",
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function parsePastItinerary($node)
    {
        $this->logger->notice(__METHOD__);
        $h = $this->itinerariesMaster->createHotel();

        $confNo = $node->bookingNumber;
        $this->logger->info("Parse Itinerary #{$confNo}", ['Header' => 3]);

        $h->general()->confirmation($confNo, 'Booking number');
        $h->general()->traveller(beautifulName($node->guestName), true);

        $h->general()->date2($node->insertDate, null, 'F d, Y');

        $h->hotel()->name($node->resortName);

        $h->program()->earnedAwards($node->pointsGained);

        $h->booked()->checkIn2($node->checkIn, null, 'F d, Y');
        $h->booked()->checkOut2($node->checkOut, null, 'F d, Y');

        $h->price()->total($node->dollarsSpent);
        $h->price()->spentAwards($node->pointsUsed);
        $h->hotel()->noAddress();
    }

    private function parseFutureItinerary($node)
    {
        $this->logger->notice(__METHOD__);
        $h = $this->itinerariesMaster->createHotel();

        $confNo = $node->bookingNumber;
        $this->logger->info("Parse Itinerary #{$confNo}", ['Header' => 3]);

        $h->general()->confirmation($confNo, 'Booking number');
        $h->general()->traveller(beautifulName($node->firstName), true);

        $h->hotel()->name($node->resortName);

        $h->program()->earnedAwards($node->pointsGained);

        $h->price()->spentAwards($node->pointsUsed);

        $h->hotel()->noAddress();

        $h->booked()->checkIn2($node->arrivalDate, null, 'F d, Y');
        $checkIn = DateTime::createFromFormat('F d, Y', $node->arrivalDate);
        $checkOut = $checkIn->add(new DateInterval('P'.$node->nights.'D'));
        $this->logger->debug("Check Out: ".$checkOut->format('Y-m-d'));
        $h->booked()->checkOut2($checkOut->format('Y-m-d'));
    }
}
