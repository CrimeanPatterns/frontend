<?php

use AwardWallet\Common\OneTimeCode\OtcHelper;

class TAccountCheckerRyanair extends TAccountChecker
{
    use PriceTools;
//    use OtcHelper;
    use SeleniumCheckerHelper;

    protected $_auth;     // [customerId, token]
    protected $_profile;
    protected $_booking;

    private $notUpcomming = false;
    private $notPast = false;

    public function InitBrowser()
    {
        parent::InitBrowser();
        $this->KeepState = true;
        $this->http->setDefaultHeader("Origin", "https://www.ryanair.com");
    }

    public static function FormatBalance($fields, $properties)
    {
        if (isset($properties['Currency'])) {
            switch ($properties['Currency']) {
                case 'GBP':
                    return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "&pound;%0.2f");

                case 'EUR':
                    return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "€%0.2f");

                case 'USD':
                    return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "$%0.2f");

                case 'HUF':
                    return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "Ft%0.2f");

                case 'NOK':
                    return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "Nkr %0.2f");

                default:
                    return formatFullBalance($fields['Balance'], $fields['ProviderCode'], "%0.2f " . $properties['Currency']);
            }
        }

        return parent::FormatBalance($fields, $properties);
    }

    public function LoadLoginForm()
    {
        $this->http->removeCookies();
        $this->http->GetURL('https://www.ryanair.com/gb/en/');

        if ($this->http->Response['code'] != 200 || strstr($this->http->currentUrl(), 'maintenance')) {
            return $this->checkErrors();
        }

        $this->seleniumAuth();

        return true;

        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Referer'      => 'https://www.ryanair.com/gb/en/',
        ];

        foreach ($headers as $headerKey => $headerVal) {
            $this->http->setDefaultHeader($headerKey, $headerVal);
        }

        $this->http->RetryCount = 0;
        $data = [
            'email'        => $this->AccountFields['Login'],
            'password'     => $this->AccountFields['Pass'],
            'policyAgreed' => 'true',
        ];
        $this->http->PostURL('https://www.ryanair.com/api/usrprof/v2/accountLogin?market=en-gb', json_encode($data));
        $this->http->RetryCount = 2;

        return true;
    }

    public function checkErrors()
    {
        $this->logger->notice(__METHOD__);
        // maintenance
        if ($message = $this->http->FindSingleNode("
                //h1[contains(text(), 'Sorry website is unavailable due to scheduled maintenance....')]
                | //p[contains(text(), 'Our website/app is currently undergoing scheduled maintenance')]
            ")
        ) {
            throw new CheckException($message . " We will be back shortly. Thank you for your patience.", ACCOUNT_PROVIDER_ERROR);
        }

        if ($message = $this->http->FindSingleNode("//p[contains(text(), 'Our site is currently down for maintenance.')]")) {
            throw new CheckException($message, ACCOUNT_PROVIDER_ERROR);
        }

        if (
            $this->http->FindSingleNode("//h2[contains(text(), 'The request could not be satisfied.')]")
            || $this->http->FindSingleNode("//h1[contains(text(), '503 Service Temporarily Unavailable')]")
        ) {
            throw new CheckException(self::PROVIDER_ERROR_MSG, ACCOUNT_PROVIDER_ERROR);
        }

        return false;
    }

    public function finalRequest($response)
    {
        $this->logger->notice(__METHOD__);

        if (!empty($response->customerId)) {
            $this->_auth = $response;
            $this->http->setDefaultHeader('X-AUTH-TOKEN', $this->_auth->token);

            $this->http->GetURL("https://www.ryanair.com/api/usrprof/v2/loggedin");
            $response = $this->http->JsonLog();

            if (false !== $response && isset($response->email)) {
                $this->_profile = $response;

                return true;
            }// if (false !== $response && isset($response->email))
        }// if (!empty($response->customerId))

        return false;
    }

    public function Login()
    {
        $response = $this->http->JsonLog();

        if ($this->finalRequest($response)) {
            return true;
        }

        $message = $response->message ?? null;

        // Please enter the 8 character verification code sent to ...
        if (
            $message == "Unknown device fingerprint"
            && isset($response->additionalData[0]->code, $response->additionalData[0]->message)
            && $response->additionalData[0]->code == 'Mfa.Token'
        ) {
            $this->State['mfaToken'] = $response->additionalData[0]->message;
            $this->AskQuestion("Please enter the 8 character verification code sent to {$this->AccountFields['Login']}", null, "Question");

            return false;
        }

        // Invalid credentials
        if (401 == $this->http->Response['code']) {
            throw new CheckException('Invalid email address or password', ACCOUNT_INVALID_PASSWORD);
        }

        if ($attempts = $this->http->FindPreg('/"code":"Account.Password.TryCount.Remaining","message":\"(\d+)/')) {
            throw new CheckException("Incorrect email address or password, {$attempts} attempts left", ACCOUNT_INVALID_PASSWORD);
        }

        // An eight-digit security code has been sent to your email. Please enter the code below
        if ($this->http->FindPreg('/"code":"Account.Unverified","message":/')) {
            $this->throwProfileUpdateMessageException();
        }
        // Account.Deactivated
        if ($this->http->FindPreg('/"code":"Account\.Deactivated","message":"DEACTIVATED"/')) {
            throw new CheckException("Oops! Not an active account. It looks like you deactivated your myRyanair account. Do you want to reactivate your account?", ACCOUNT_PROVIDER_ERROR);
        }
        // Account is locked
        if ($this->http->FindPreg("/\"message\":\"Account is locked\. Try count exceeded\.\"/")) {
            throw new CheckException('Account is locked.', ACCOUNT_LOCKOUT);
        }
        // Privacy Policy
        if ($this->http->FindPreg("/\"message\":\"Policy not agreed on.\"/")) {
            $this->throwProfileUpdateMessageException();
        }
        // Something went wrong
        if ($this->http->FindPreg("/\"message\":\"Something went wrong\"/")) {
            throw new CheckException("Something went wrong. Please try again later.", ACCOUNT_PROVIDER_ERROR);
        }

        return $this->checkErrors();
    }

    public function ProcessStep($step)
    {
        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);
        $data = [
            "mfaToken" => $this->State['mfaToken'],
            "mfaCode"  => $answer,
        ];
        $headers = [
            "Accept"       => "application/json, text/plain, */*",
            "Content-Type" => "application/json",
        ];
        $this->http->RetryCount = 0;
        $this->http->PutURL("https://api.ryanair.com/api/usrprof/v2/accountVerifications/deviceFingerprint?market=en-gb", json_encode($data), $headers);
        $this->http->RetryCount = 2;
        $response = $this->http->JsonLog();
        // {"code":"Mfa.Wrong.Code","message":"Mfa wrong code","additionalData":[{"code":"Mfa.Available.Attempts","message":"4"}]}
        if (isset($response->message) && $response->message == "Mfa wrong code") {
            $this->AskQuestion($this->Question, "Invalid security code. Please check your email and enter code again", "Question");

            return false;
        }

        return $this->finalRequest($response);
    }

    public function Parse()
    {
        $this->http->GetURL("https://www.ryanair.com/api/usrprof/rest/api/v1/secure/users/{$this->_auth->customerId}/bookings/pinned");
        $tripSaved = $this->http->JsonLog();

        if (false === $tripSaved
                || null === $tripSaved
                || 0 === count((array) $tripSaved)) {
            $tripSavedCount = 0;
        } else {
            $tripSavedCount = count((array) $tripSaved);
        }
        // Saved trips
        $this->SetProperty('TripSaved', $tripSavedCount);

        $this->http->GetURL("https://www.ryanair.com/api/usrprof/rest/api/v1/secure/users/{$this->_auth->customerId}/bookings/upcoming/all?includeHold=true&start=0");
        $tripUpcoming = $this->http->JsonLog();

        if ($tripUpcoming && isset($tripUpcoming->count)) {
            $tripUpcomingCount = intval($tripUpcoming->count);
        }
        // Name
        $firstName = $this->_profile->firstName ?? '';
        $lastName = $this->_profile->lastName ?? '';
        $name = beautifulName(trim($firstName . ' ' . $lastName));

        if ($name) {
            $this->SetProperty('Name', $name);
        }

        if (isset($tripUpcomingCount)) {
            // Upcoming trips
            $this->SetProperty('TripUpcoming', $tripUpcomingCount);

            $this->_booking = $tripUpcoming;

            if ($tripUpcomingCount > 0) {
                if (empty($tripUpcoming->Bookings[0]->Flights)) { // for booking: car, hotels
                    $this->sendNotification('fish - refs #12969 [ryanair] valid account :: upcoming trip');
                }
            }
        }

        $this->http->GetURL("https://www.ryanair.com/api/usrprof/v2/customers/{$this->_auth->customerId}/profile");
        $response = $this->http->JsonLog();
        // Member Since
        if (false !== $response && !empty($response->memberSince)) {
            $this->SetProperty('MemberSince', date('d F, Y', strtotime($response->memberSince)));
        }

        // Balance
        $this->http->GetURL("https://www.ryanair.com/api/usrprof/v2/customers/{$this->_auth->customerId}/vouchers/creditBalance?currency=EUR");
        $response = $this->http->JsonLog();
        $this->SetBalance($response->amountAvailable);
        $this->SetProperty('Currency', "EUR");

        $this->http->GetURL("https://www.ryanair.com/api/usrprof/v2/customers/{$this->_auth->customerId}/vouchers");
        $response = $this->http->JsonLog();

        if (!empty($response->vouchers)) {
            $this->SetProperty("CombineSubAccounts", false);

            foreach ($response->vouchers as $voucher) {
                $this->AddSubAccount([
                    'Code'           => 'WalletVoucher' . $voucher->pnr,
                    'DisplayName'    => "Wallet (Conf # {$voucher->pnr})",
                    'Balance'        => $voucher->amountAvailable,
                    'Currency'       => $voucher->currencyCode,
                    "ExpirationDate" => strtotime($voucher->expiration),
                ], true);
            }
        }
    }

    public function ParseItineraries()
    {
        $this->parseItinerary();

        if ($this->ParsePastIts) {
            $this->parseItinerary('false');
        }

        if ($this->notUpcomming === true && !$this->ParsePastIts) {
            $this->itinerariesMaster->setNoItineraries(true);
        } elseif ($this->notUpcomming === true && $this->notPast === true) {
            $this->itinerariesMaster->setNoItineraries(true);
        }
    }

    public function parseItinerary($active = 'true')
    {
        $this->logger->notice(__METHOD__);
        $this->http->GetURL("https://www.ryanair.com/api/orders/v2/orders/{$this->_auth->customerId}?active=$active&order=ASC");
        $bookings = $this->http->JsonLog();

        if (empty($bookings->items)) {
            if ($this->http->FindPreg('/{"items":\[\],/')) {
                if ($active === 'true') {
                    $this->notUpcomming = true;
                } else {
                    $this->notPast = true;
                }
            }

            return [];
        }

        $headers = [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
        ];

        foreach ($bookings->items as $item) {
            foreach ($item->flights as $flight) {
                /*
                $data = [
                    'query'     => 'query GetBookingByBookingId($bookingInfo: GetBookingByBookingIdInputType, $authToken: String!) {\n  getBookingByBookingId(bookingInfo: $bookingInfo, authToken: $authToken) {\n    addons {\n      ...AddonFrag\n    }\n    carHire {\n      ...CarHireFrag\n    }\n    contacts {\n      ...ContactsFrag\n    }\n    extras {\n      ...ExtrasOrFeesFrag\n    }\n    groundTransfer {\n      ...GroundTransferFrag\n    }\n    hotels {\n      ...HotelsFrag\n    }\n    info {\n      ...InfoFrag\n    }\n    journeys {\n      ...JourneysFrag\n    }\n    passengers {\n      ...PassengersFrag\n    }\n    payments {\n      ...PaymentInfoFrag\n    }\n    fees {\n      ...ExtrasOrFeesFrag\n    }\n    serverTimeUTC\n    sessionToken\n    tripId\n  }\n}\n\n\nfragment AddonFrag on AddOnResponseModelType {\n  carParkName\n  code\n  currLater\n  currNow\n  dropOffLocation\n  end\n  isSingleOffer\n  itemId\n  loc\n  name\n  pax\n  paxNum\n  pickUpLocation\n  provider\n  providerCode\n  qty\n  refNo\n  sold\n  src\n  start\n  status\n  total\n  type\n}\n\nfragment CarHireFrag on CarHireResponseModelType {\n  carSupplierConfirmationId\n  carType\n  confirmationId\n  currencyCode\n  insurance\n  pickupDateTime\n  pickupLocation\n  returnDateTime\n  returnLocation\n  serviceProvider\n  sold\n  status\n  totalPrice\n}\n\nfragment PassengerNameFrag on PassengerNameResponseModelType {\n  first\n  last\n  middle\n  suffix\n  title\n}\n\nfragment ContactsFrag on ContactResponseModelType {\n  address\n  city\n  country\n  cultureCode\n  email\n  fax\n  homePhone\n  name {\n    ...PassengerNameFrag\n  }\n  otherPhone\n  postalCode\n  provinceState\n  type\n  workPhone\n}\n\nfragment ExtrasOrFeesFrag on BookingExtraResponseModelType {\n  amt\n  code\n  includedSsrs\n  isPartOfBundle\n  isSeatChange\n  journeyNum\n  percentageDiscount\n  qty\n  segmentNum\n  sold\n  total\n  totalDiscount\n  totalWithoutDiscount\n  type\n  vat\n}\n\nfragment GroundTransferFrag on GroundTransferResponseModelType {\n  confirmationId\n  currencyCode\n  dropoffDateTime\n  dropoffLocation\n  flightBookingId\n  isSold\n  pickupDateTime\n  pickupLocation\n  pnr\n  status\n  totalPrice\n}\n\nfragment HotelsFrag on HotelsResponseModelType {\n  status\n}\n\nfragment InfoFrag on BookingInfoResponseModelType {\n  allSeatsAutoAllocated\n  balanceDue\n  bookingAgent\n  bookingId\n  createdUtcDate\n  curr\n  currPrecision\n  domestic\n  holdDateTime\n  isConnectingFlight\n  isBuyOneGetOneDiscounted\n  isHoldable\n  isPrime\n  modifiedUtcDate\n  pnr\n  status\n  locationCode\n  locationCodeGroup\n  sourceOrganization\n  companyName\n}\n\nfragment JourneyChangeFrag on JourneyChangeInfoResponseModelType {\n  freeMove\n  isChangeable\n  isChanged\n  reasonCode\n}\n\nfragment FaresFrag on BookingFareResponseModelType {\n  amt\n  code\n  disc\n  fareKey\n  fat\n  includedSsrs\n  percentageDiscount\n  qty\n  sold\n  total\n  totalDiscount\n  totalWithoutDiscount\n  type\n  vat\n}\n\nfragment FatsFrag on BookingFatResponseModelType {\n  amount\n  code\n  total\n  vat\n  description\n  qty\n}\n\nfragment SeatRowDeltaFrag on PaxSeatRowDeltaResponseModelType {\n  rowDistance\n  segmentNum\n}\n\nfragment SegmentsFrag on SegmentModelResponseModelType {\n  aircraft\n  arrive\n  arriveUTC\n  depart\n  departUTC\n  dest\n  duration\n  flown\n  flt\n  isCancelled\n  isDomestic\n  orig\n  segmentNum\n  vatRate\n}\n\nfragment ZoneDiscountFrag on BookingZoneDiscountResponseModelType {\n  code\n  pct\n  total\n  zone\n}\n\nfragment JourneysFrag on BookingJourneyResponseModelType {\n  arrive\n  arriveUTC\n  changeInfo {\n    ...JourneyChangeFrag\n  }\n  checkInCloseUtcDate\n  checkInFreeAllocateOpenUtcDate\n  checkInOpenUtcDate\n  depart\n  departUTC\n  dest\n  destCountry\n  duration\n  fareClass\n  fareOption\n  fares {\n    ...FaresFrag\n  }\n  fareType\n  fats {\n    ...FatsFrag\n  }\n  flt\n  infSsrs {\n    ...ExtrasOrFeesFrag\n  }\n  isRescheduledJourneyWithFreeMove\n  setaSsrs {\n    ...ExtrasOrFeesFrag\n  }\n  journeyNum\n  maxPaxSeatRowDistance {\n    ...SeatRowDeltaFrag\n  }\n  mobilebp\n  orig\n  origCountry\n  seatsLeft\n  segments {\n    ...SegmentsFrag\n  }\n  zoneDiscount {\n    ...ZoneDiscountFrag\n  }\n}\n\nfragment ResidentInfoFrag on PassengerResidentInfoResponseModelType {\n  community\n  dateOfBirth\n  dob\n  docNum\n  docType\n  hasLargeFamilyDiscount\n  hasResidentDiscount\n  largeFamilyCert\n  municipality\n  saraValidationCode\n}\n\nfragment SegmentCheckinFrag on PassengerSegmentCheckinResponseModelType {\n  journeyNum\n  segmentNum\n  status\n}\n\nfragment TravelDocumentFrag on TravelDocumentResponseModelType {\n  countryOfIssue\n  dateOfBirth\n  dOB\n  docNumber\n  docType\n  expiryDate\n  nationality\n  specialVisaDetails {\n    countryOfIssue\n    docNumber\n    docType\n  }\n}\n\nfragment PassengerWithInfantTravelDocumentsFrag on PassengerWithInfantTravelDocumentResponseModelType {\n  num\n  travelDocument {\n    ...TravelDocumentFrag\n  }\n  infantTravelDocument {\n    ...TravelDocumentFrag\n  }\n}\n\nfragment PassengersFrag on PassengerResponseModelType {\n  dateOfBirth\n  doB\n  ins\n  inf {\n    dateOfBirth\n    dob\n    first\n    last\n    middle\n    suffix\n    title\n  }\n  name {\n    ...PassengerNameFrag\n  }\n  nationality\n  paxFees {\n    ...ExtrasOrFeesFrag\n  }\n  paxNum\n  residentInfo {\n    ...ResidentInfoFrag\n  }\n  segCheckin {\n    ...SegmentCheckinFrag\n  }\n  segFees {\n    ...ExtrasOrFeesFrag\n  }\n  segPrm {\n    ...ExtrasOrFeesFrag\n  }\n  segSeats {\n    ...ExtrasOrFeesFrag\n  }\n  segSsrs {\n    ...ExtrasOrFeesFrag\n  }\n  travelDocuments {\n    ...PassengerWithInfantTravelDocumentsFrag\n  }\n  type\n}\n\nfragment PaymentInfoFrag on PaymentInfoResponseModelType {\n  accName\n  accNum\n  amt\n  code\n  currency\n  dccAmt\n  dccApplicable\n  dccCurrency\n  dccRate\n  discount\n  isReward\n  status\n  type\n  createdDate\n  invoiceNumber\n}\n\n',
                    'variables' => [
                        'authToken'   => $this->http->getCookieByName('SESSION_COOKIE'),
                        'bookingInfo' => [
                            'bookingId'   => $flight->bookingId,
                            'surrogateId' => $item->customerId,
                        ],
                    ],
                ];
                */
                $data = '{"query":"query GetBookingByBookingId($bookingInfo: GetBookingByBookingIdInputType, $authToken: String!) {\n  getBookingByBookingId(bookingInfo: $bookingInfo, authToken: $authToken) {\n    addons {\n      ...AddonFrag\n    }\n    carHire {\n      ...CarHireFrag\n    }\n    contacts {\n      ...ContactsFrag\n    }\n    extras {\n      ...ExtrasOrFeesFrag\n    }\n    groundTransfer {\n      ...GroundTransferFrag\n    }\n    hotels {\n      ...HotelsFrag\n    }\n    info {\n      ...InfoFrag\n    }\n    journeys {\n      ...JourneysFrag\n    }\n    passengers {\n      ...PassengersFrag\n    }\n    payments {\n      ...PaymentInfoFrag\n    }\n    fees {\n      ...ExtrasOrFeesFrag\n    }\n    serverTimeUTC\n    sessionToken\n    tripId\n  }\n}\n\n\nfragment AddonFrag on AddOnResponseModelType {\n  carParkName\n  code\n  currLater\n  currNow\n  dropOffLocation\n  end\n  isSingleOffer\n  itemId\n  loc\n  name\n  pax\n  paxNum\n  pickUpLocation\n  provider\n  providerCode\n  qty\n  refNo\n  sold\n  src\n  start\n  status\n  total\n  type\n}\n\nfragment CarHireFrag on CarHireResponseModelType {\n  carSupplierConfirmationId\n  carType\n  confirmationId\n  currencyCode\n  insurance\n  pickupDateTime\n  pickupLocation\n  returnDateTime\n  returnLocation\n  serviceProvider\n  sold\n  status\n  totalPrice\n}\n\nfragment PassengerNameFrag on PassengerNameResponseModelType {\n  first\n  last\n  middle\n  suffix\n  title\n}\n\nfragment ContactsFrag on ContactResponseModelType {\n  address\n  city\n  country\n  cultureCode\n  email\n  fax\n  homePhone\n  name {\n    ...PassengerNameFrag\n  }\n  otherPhone\n  postalCode\n  provinceState\n  type\n  workPhone\n}\n\nfragment ExtrasOrFeesFrag on BookingExtraResponseModelType {\n  amt\n  code\n  includedSsrs\n  isPartOfBundle\n  isSeatChange\n  journeyNum\n  percentageDiscount\n  qty\n  segmentNum\n  sold\n  total\n  totalDiscount\n  totalWithoutDiscount\n  type\n  vat\n}\n\nfragment GroundTransferFrag on GroundTransferResponseModelType {\n  confirmationId\n  currencyCode\n  dropoffDateTime\n  dropoffLocation\n  flightBookingId\n  isSold\n  pickupDateTime\n  pickupLocation\n  pnr\n  status\n  totalPrice\n}\n\nfragment HotelsFrag on HotelsResponseModelType {\n  status\n}\n\nfragment InfoFrag on BookingInfoResponseModelType {\n  allSeatsAutoAllocated\n  balanceDue\n  bookingAgent\n  bookingId\n  createdUtcDate\n  curr\n  currPrecision\n  domestic\n  holdDateTime\n  isConnectingFlight\n  isBuyOneGetOneDiscounted\n  isHoldable\n  isPrime\n  modifiedUtcDate\n  pnr\n  status\n  locationCode\n  locationCodeGroup\n  sourceOrganization\n  companyName\n}\n\nfragment JourneyChangeFrag on JourneyChangeInfoResponseModelType {\n  freeMove\n  isChangeable\n  isChanged\n  reasonCode\n}\n\nfragment FaresFrag on BookingFareResponseModelType {\n  amt\n  code\n  disc\n  fareKey\n  fat\n  includedSsrs\n  percentageDiscount\n  qty\n  sold\n  total\n  totalDiscount\n  totalWithoutDiscount\n  type\n  vat\n}\n\nfragment FatsFrag on BookingFatResponseModelType {\n  amount\n  code\n  total\n  vat\n  description\n  qty\n}\n\nfragment SeatRowDeltaFrag on PaxSeatRowDeltaResponseModelType {\n  rowDistance\n  segmentNum\n}\n\nfragment SegmentsFrag on SegmentModelResponseModelType {\n  aircraft\n  arrive\n  arriveUTC\n  depart\n  departUTC\n  dest\n  duration\n  flown\n  flt\n  isCancelled\n  isDomestic\n  orig\n  segmentNum\n  vatRate\n}\n\nfragment ZoneDiscountFrag on BookingZoneDiscountResponseModelType {\n  code\n  pct\n  total\n  zone\n}\n\nfragment JourneysFrag on BookingJourneyResponseModelType {\n  arrive\n  arriveUTC\n  changeInfo {\n    ...JourneyChangeFrag\n  }\n  checkInCloseUtcDate\n  checkInFreeAllocateOpenUtcDate\n  checkInOpenUtcDate\n  depart\n  departUTC\n  dest\n  destCountry\n  duration\n  fareClass\n  fareOption\n  fares {\n    ...FaresFrag\n  }\n  fareType\n  fats {\n    ...FatsFrag\n  }\n  flt\n  infSsrs {\n    ...ExtrasOrFeesFrag\n  }\n  isRescheduledJourneyWithFreeMove\n  setaSsrs {\n    ...ExtrasOrFeesFrag\n  }\n  journeyNum\n  maxPaxSeatRowDistance {\n    ...SeatRowDeltaFrag\n  }\n  mobilebp\n  orig\n  origCountry\n  seatsLeft\n  segments {\n    ...SegmentsFrag\n  }\n  zoneDiscount {\n    ...ZoneDiscountFrag\n  }\n}\n\nfragment ResidentInfoFrag on PassengerResidentInfoResponseModelType {\n  community\n  dateOfBirth\n  dob\n  docNum\n  docType\n  hasLargeFamilyDiscount\n  hasResidentDiscount\n  largeFamilyCert\n  municipality\n  saraValidationCode\n}\n\nfragment SegmentCheckinFrag on PassengerSegmentCheckinResponseModelType {\n  journeyNum\n  segmentNum\n  status\n}\n\nfragment TravelDocumentFrag on TravelDocumentResponseModelType {\n  countryOfIssue\n  dateOfBirth\n  dOB\n  docNumber\n  docType\n  expiryDate\n  nationality\n  specialVisaDetails {\n    countryOfIssue\n    docNumber\n    docType\n  }\n}\n\nfragment PassengerWithInfantTravelDocumentsFrag on PassengerWithInfantTravelDocumentResponseModelType {\n  num\n  travelDocument {\n    ...TravelDocumentFrag\n  }\n  infantTravelDocument {\n    ...TravelDocumentFrag\n  }\n}\n\nfragment PassengersFrag on PassengerResponseModelType {\n  dateOfBirth\n  doB\n  ins\n  inf {\n    dateOfBirth\n    dob\n    first\n    last\n    middle\n    suffix\n    title\n  }\n  name {\n    ...PassengerNameFrag\n  }\n  nationality\n  paxFees {\n    ...ExtrasOrFeesFrag\n  }\n  paxNum\n  residentInfo {\n    ...ResidentInfoFrag\n  }\n  segCheckin {\n    ...SegmentCheckinFrag\n  }\n  segFees {\n    ...ExtrasOrFeesFrag\n  }\n  segPrm {\n    ...ExtrasOrFeesFrag\n  }\n  segSeats {\n    ...ExtrasOrFeesFrag\n  }\n  segSsrs {\n    ...ExtrasOrFeesFrag\n  }\n  travelDocuments {\n    ...PassengerWithInfantTravelDocumentsFrag\n  }\n  type\n}\n\nfragment PaymentInfoFrag on PaymentInfoResponseModelType {\n  accName\n  accNum\n  amt\n  code\n  currency\n  dccAmt\n  dccApplicable\n  dccCurrency\n  dccRate\n  discount\n  isReward\n  status\n  type\n  createdDate\n  invoiceNumber\n}\n\n","variables":{"authToken":"'.$this->http->getCookieByName('SESSION_COOKIE').'","bookingInfo":{"bookingId":"'.$flight->bookingId.'","surrogateId":"'.$item->customerId.'"}}}';
                $this->http->PostURL("https://www.ryanair.com/api/bookingfa/en-us/graphql", ($data), $headers);
                $response = $this->http->JsonLog();

                if ($this->http->FindPreg('/\[\{"message":"DotRez: BookingWithoutJourneys","extensions"/')) {
                    $this->logger->error('Something went wrong');

                    continue;
                }

                if ($this->http->FindPreg('/\[\{"message":"DotRez: (?:BookingNotVerified|BalanceDue)","extensions"/')) {
                    $this->logger->error('This booking was made through a third party travel agent who has no commercial relationship with Ryanair to sell our flights. You need to verify this booking in order to be able to manage it by yourself.');

                    continue;
                }

                $this->parseItineraryV2($response);
            }
        }

        return [];
    }

    public function parseItineraryV2($item)
    {
        $this->logger->notice(__METHOD__);
        $f = $this->itinerariesMaster->createFlight();

        if (
            isset($item->errors[0]->message)
            && (in_array($item->errors[0]->message, [
                "DotRez: NoAccessToBookingAllowed",
                "DotRez: NoBooking",
            ])
                || strstr($item->errors[0]->message , "Unexpected character ")
            )
        ) {
            return;
        }

        $conf = $item->data->getBookingByBookingId->info->pnr;
        $this->logger->info(sprintf('Parse Itinerary #%s', $conf), ['Header' => 3]);
        $f->general()->confirmation($conf)
            ->status(beautifulName($item->data->getBookingByBookingId->info->status));

        foreach ($item->data->getBookingByBookingId->passengers as $traveller) {
            $f->general()->traveller("{$traveller->name->first} {$traveller->name->last}");
        }

        foreach ($item->data->getBookingByBookingId->journeys as $journeys) {
            foreach ($journeys->segments as $segment) {
                $s = $f->addSegment();

                if (preg_match('/([A-Z]{2})\s*(\d+)/', $segment->flt, $m)) {
                    $s->airline()->name($m[1]);
                    $s->airline()->number($m[2]);
                }

                // 2023-11-30T18:20:00
                $s->departure()->date2($segment->depart);
                $s->arrival()->date2($segment->arrive);

                $this->http->GetURL("https://www.ryanair.com/api/views/locate/5/airports/en/$segment->orig");
                $orig = $this->http->JsonLog();
                $s->arrival()->name($orig->name);
                $s->departure()->code($segment->orig);

                $this->http->GetURL("https://www.ryanair.com/api/views/locate/5/airports/en/$segment->dest");
                $dest = $this->http->JsonLog();
                $s->arrival()->name($dest->name);
                $s->arrival()->code($segment->dest);

                // 03:00
                $duration = explode(':', $segment->duration);

                if (isset($duration) && count($duration) == 2) {
                    if ((int) $duration[1] > 0) {
                        $duration = sprintf('%01dh %01dm', (int) $duration[0], $duration[1]);
                    } else {
                        $duration = sprintf('%01dh', (int) $duration[0]);
                    }
                    $s->extra()->duration($duration);
                }

                $seats = [];

                foreach ($item->data->getBookingByBookingId->passengers as $traveller) {
                    foreach ($traveller->segSeats as $seat) {
                        if ($seat->segmentNum == $segment->segmentNum) {
                            $seats[] = $seat->code;
                        }
                    }
                }

                if (!empty($seats)) {
                    $s->extra()->seats(array_unique($seats));
                }
            }
        }

        $this->logger->debug('Parsed Itinerary:');
        $this->logger->debug(var_export($f->toArray(), true), ['pre' => true]);
    }

    public function GetConfirmationFields()
    {
        return [
            'ConfNo' => [
                'Caption'  => 'Reservation number',
                'Type'     => 'string',
                'Size'     => 6,
                'Required' => true,
            ],
            'Email'              => [
                'Caption'  => 'Email address',
                'Type'     => 'string',
                'Size'     => 64,
                'Value'    => $this->GetUserField('Email'),
                'Required' => true,
            ],
        ];
    }

    public function ConfirmationNumberURL($arFields)
    {
        return 'https://www.ryanair.com/gb/en/mytrips/summary';
    }

    public function CheckConfirmationNumberInternal($arFields, &$it)
    {
        $this->http->GetURL('https://www.ryanair.com/gb/en/check-in');
        $this->http->RetryCount = 0;
        $this->http->PostURL('https://www.ryanair.com/api/bookingfa/api/en-gb/reservation/getbookingbyreservationnumber', json_encode([
            'EmailAddress'      => $arFields['Email'],
            'ReservationNumber' => $arFields['ConfNo'],
        ]), [
            'Accept'       => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json;charset=utf-8',
        ]);
        $this->http->RetryCount = 2;

        $code = null;
        $response = $this->http->JsonLog(null, 2);

        if (isset($response->errors) && is_array($response->errors)
            && isset($response->errors[0]->extensions)
            && isset($response->errors[0]->extensions->code)
        ) {
            $code = $response->errors[0]->extensions->code;
        } elseif (isset($response->code)) {
            $code = $response->code;
        }

        if (isset($code)) {
            if ('InvalidRequestFields' === $code) {
                return "We're sorry. We could not find your booking";
            }

            if ('BookingWithoutJourneys' === $code) {
                return "We’re sorry, we could not find your booking. Please check the reservation number and email address is correct.";
            }
            $this->sendNotification("check error code // ZM");

            return null;
        }

        if (isset($response->journeys[0]->segments)) {
            $it = [$this->parseAir($response)];
        }

        return null;
    }

    private function parseAir($booking)
    {
        $this->logger->notice(__METHOD__);

        if (empty($booking->journeys) || !isset($booking->info->status)) {
            return [];
        }

        if (empty($booking->info->pnr)) {
            $this->logger->error('Skip: pnr empty');

            return [];
        }

        $recordLocator = $booking->info->pnr;
        $this->logger->info("Parse Itinerary #{$recordLocator}", ['Header' => 3]);
        $segs = [];
        $passengers = [];

        foreach ($booking->journeys as $key => $journey) {
            if (isset($journey->depart)) {
                $depDate = $journey->depart;
            } elseif (isset($journey->departUTC)) {
                $depDate = $journey->departUTC;
            } else {
                $depDate = null;
            }

            if (isset($journey->arrive)) {
                $arrDate = $journey->arrive;
            } elseif (isset($journey->arriveUTC)) {
                $arrDate = $journey->arriveUTC;
            } else {
                $arrDate = null;
            }
            $flight = $journey->flt;
            $airlineName = $this->http->FindPreg('/^(\w{2})\s+/', false, $flight);
            $seg = [
                'FlightNumber' => filter_var($flight, FILTER_SANITIZE_NUMBER_INT),
                'AirlineName'  => $airlineName == 'OE' ? 'LDM' : $airlineName, // refs #16839, hard code
                'Aircraft'     => $journey->aircraft ?? $journey->segments[0]->aircraft,
                'DepCode'      => $journey->orig,
                'DepDate'      => strtotime($depDate),
                'ArrCode'      => $journey->dest,
                'ArrDate'      => strtotime($arrDate),
            ];

            if (isset($booking->passengers)) {
                foreach ($booking->passengers as $item) {
                    $passengers[] = beautifulName(trim($item->name->title . ' ' . $item->name->first . ' ' . $item->name->last));

                    if (!empty($item->segSeats)) {
                        foreach ($item->segSeats as $k => $seat) {
                            if ($k == $key && isset($seat->code)) {
                                $seg['Seats'][] = $seat->code;
                            }
                        }
                    }// if (!empty($item->segSeats))
                }// foreach ($booking->passengers as $item)

                if (!empty($seg['Seats'])) {
                    $this->logger->debug('Seats:');
                    $this->logger->debug(var_export($seg['Seats'], true), ["pre" => true]);
                }// if (!empty($data['Seats']))
            }// if (isset($booking->passengers))
            $segs[] = $seg;
        }// foreach ($booking->journeys as $key => $journey)

        $data = [
            'Kind'          => 'T',
            'RecordLocator' => $recordLocator,
            'Status'        => $booking->info->status,
            'TripSegments'  => $segs,
            'Passengers'    => array_unique($passengers),
        ];

        if (isset($booking->payments)) {
            $totalCost = 0;

            for ($i = -1, $iCount = count($booking->payments); ++$i < $iCount;) {
                $totalCost += $booking->payments[$i]->amt;
            }
            $data['TotalCharge'] = $totalCost;
            $data['Currency'] = $this->currency($booking->payments[0]->currency);

            $paymentFee = $this->getPaymentFee($booking);

            if ($paymentFee) {
                $data['TotalCharge'] -= $paymentFee;
                $data['TotalCharge'] = round($data['TotalCharge'], 2);
            }
        }

        $this->logger->debug('Parsed Itinerary:');
        $this->logger->debug(var_export($data, true), ['pre' => true]);

        return $data;
    }

    private function getPaymentFee($booking)
    {
        $this->logger->notice(__METHOD__);

        if (!isset($booking->fees)) {
            return null;
        }

        $res = 0;

        foreach ($booking->fees as $fee) {
            if ($fee->type === 'PaymentFee') {
                $res += $fee->amt;

                if ($fee->qty !== 1) {
                    $this->sendNotification('check payment fee // MI');
                }
            }
        }

        $this->logger->info("paymentFee = {$res}");

        return $res;
    }

    public function seleniumAuth()
    {
        $this->logger->notice(__METHOD__);
        $selenium = clone $this;
        $result = false;
        $retry = false;
        $this->http->brotherBrowser($selenium->http);

        try {
            $this->logger->notice("Running Selenium...");
            $selenium->UseSelenium();
            $selenium->seleniumOptions->recordRequests = true;
            $selenium->http->saveScreenshots = true;

            $selenium->useGoogleChrome();

            $selenium->http->start();
            $selenium->Start();

            $selenium->http->GetURL("https://www.ryanair.com/gb/en/trip/manage");

            if ($acceptCookies = $selenium->waitForElement(WebDriverBy::xpath('//button[contains(text(), "Yes, I agree")]'), 10)) {
                $acceptCookies->click();
                $this->savePageToLogs($selenium);
            }

            if ($iframeSurvey = $selenium->waitForElement(WebDriverBy::xpath('//iframe[@class="kyc-iframe"]'), 3)) {
                $selenium->driver->switchTo()->frame($iframeSurvey);
            }

            // login
            $loginInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@name="email"]'), 10);
            // password
            $passwordInput = $selenium->waitForElement(WebDriverBy::xpath('//input[@name="password"]'), 0);
            // Sign In
            $button = $selenium->waitForElement(WebDriverBy::xpath('//button[contains(text(), "Log in")]'), 0);
            $this->savePageToLogs($selenium);

            if (!$loginInput || !$passwordInput || !$button) {
                return false;
            }

            $loginInput->sendKeys($this->AccountFields['Login']);

            $mover = new MouseMover($selenium->driver);
            $mover->logger = $this->logger;
            $mover->sendKeys($passwordInput, $this->AccountFields['Pass'], 7);
            $this->savePageToLogs($selenium);
            $this->logger->debug("submit form");
            $button->click();
            $selenium->driver->switchTo()->defaultContent();

            $logout = $selenium->waitForElement(WebDriverBy::xpath('//h1[contains(text(), "We are unable to access your Account.")]'), 10);
            $this->savePageToLogs($selenium);

            $seleniumDriver = $selenium->http->driver;
            $requests = $seleniumDriver->browserCommunicator->getRecordedRequests();

            foreach ($requests as $n => $xhr) {
                $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} " . json_encode($xhr->request->getHeaders()));
//                $this->logger->debug("xhr response {$n}: {$xhr->response->getStatus()} {$xhr->response->getHeaders()}");

                if (strpos($xhr->request->getUri(), 'api/usrprof/v2/accountLogin?market=en-gb') !== false) {
                    $this->logger->debug("xhr request {$n}: {$xhr->request->getVerb()} {$xhr->request->getUri()} " . json_encode($xhr->request->getHeaders()));
//                    $this->logger->debug("xhr response {$n}: {$xhr->response->getStatus()} {$xhr->response->getHeaders()}");
                    $this->logger->debug("xhr response {$n} body: " . json_encode($xhr->response->getBody()));
                    $responseData = json_encode($xhr->response->getBody());
                }
            }

            if (!empty($responseData)) {
                $this->http->SetBody($responseData);
                $cookies = $selenium->driver->manage()->getCookies();

                foreach ($cookies as $cookie) {
                    $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
                }

                return true;
            }

            $cookies = $selenium->driver->manage()->getCookies();

            foreach ($cookies as $cookie) {
                $this->http->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'], $cookie['expiry'] ?? null);
            }
            $this->seleniumURL = $selenium->http->currentUrl();
            $this->logger->debug("[Selenium Current URL]: {$this->seleniumURL}");

            if ($logout) {
                $this->http->GetURL($this->seleniumURL);
            }

            $result = true;
        } catch (ScriptTimeoutException $e) {
            $this->logger->error("ScriptTimeoutException: " . $e->getMessage(), ['HtmlEncode' => true]);
            $this->DebugInfo = "ScriptTimeoutException";
            // retries
            if (strstr($e->getMessage(), 'timeout')) {
                $retry = true;
            }
        }// catch (ScriptTimeoutException $e)
        finally {
            // close Selenium browser
            $selenium->http->cleanup();

            // retries
            if ($retry && ConfigValue(CONFIG_SITE_STATE) != SITE_STATE_DEBUG) {
                throw new CheckRetryNeededException(3);
            }
        }

        if (
            $this->seleniumURL == "https://global.americanexpress.com/dashboard/error"
        ) {
            throw new CheckException("Sorry, we are unable to display this account right now. Please try again later.", ACCOUNT_PROVIDER_ERROR);
        }

        return $result;
    }
}
