<?php

namespace AwardWallet\Engine\virgin\RewardAvailability;

use AwardWallet\Engine\ProxyList;
use CheckException;
use DateTime;
use DateTimeZone;
use WebDriverBy;

class Register extends \TAccountChecker
{
    use ProxyList;
    use \SeleniumCheckerHelper;

    public const MIN_PASSWORD_LENGTH = 8;

    private const URL = 'https://www.virginatlantic.com/flying-club/api/graphql';

    private $fields;

    private $headers = [
        "Accept"              => "*/*",
        "Accept-Encoding"     => "gzip, deflate, br, zstd",
        "Accept-Language"     => "en-US",
        "Origin"              => "https://www.virginatlantic.com",
        "Content-Type"        => "application/json",
        "Referer"             => "https://www.virginatlantic.com/flying-club/join",
    ];

    public function InitBrowser()
    {
        parent::InitBrowser();

        $this->useSelenium();
        $this->useFirefox(\SeleniumFinderRequest::FIREFOX_59);

        $this->http->saveScreenshots = true;

        $array = ['us', 'ca'];
        $targeting = $array[array_rand($array)];
        $this->setProxyNetNut(null, $targeting);

        $resolutions = [
            [1152, 864],
            [1280, 720],
            [1280, 768],
            [1280, 800],
            [1360, 768],
            [1366, 768],
        ];

        $this->setScreenResolution($resolutions[array_rand($resolutions)]);
    }

    public function getRegisterFields()
    {
        return [
            'FirstName' => [
                'Type'     => 'string',
                'Caption'  => 'First Name',
                'Required' => true,
            ],
            'LastName' => [
                'Type'     => 'string',
                'Caption'  => 'Last Name',
                'Required' => true,
            ],
            'Email' => [
                'Type'     => 'string',
                'Caption'  => 'Email address',
                'Required' => true,
            ],
            'PhoneNumber' => [
                'Type'     => 'string',
                'Caption'  => 'Phone Number (10 numbers length)',
                'Required' => true,
            ],
            'BirthdayDate' => [
                'Type'     => 'date',
                'Caption'  => 'Your date of birth, older than 18(MM/DD/YYYY)',
                'Required' => true,
            ],
            'Gender' => [
                'Type'     => 'string',
                'Caption'  => 'Gender',
                'Required' => true,
                'Options'  => ['male' => 'Male', 'female' => "Female"],
            ],
            'Country' => [
                'Type'     => 'string',
                'Caption'  => 'State',
                'Required' => true,
                'Options'  => ['US' => 'United States', 'CA' => 'Canada', 'MX' => 'Mexico'],
            ],
            'Address' => [
                'Type'     => 'string',
                'Caption'  => 'Address',
                'Required' => true,
                'Note'     => "Please use only the 26 English letters (A-Z), numerals (0-9), and spaces",
            ],
            'City' => [
                'Type'     => 'string',
                'Caption'  => 'City',
                'Required' => true,
                'Note'     => 'Please use only the 26 English letters (A-Z), periods (.), hyphens (-), and spaces.',
            ],
            'State' => [
                'Type'     => 'string',
                'Caption'  => 'State (US only)',
                'Required' => true,
                'Note'     => 'Please use only the 26 English letters (A-Z), periods (.), hyphens (-), and spaces.',
            ],
            'ZipCode' => [
                'Type'     => 'string',
                'Caption'  => 'Zip Code',
                'Required' => true,
                'Note'     => 'Please use only the 26 English letters (A-Z), numerals (0-9), hyphens (-), parentheses ( ), and spaces.',
            ],
            'Password' => [
                'Type'     => 'string',
                'Caption'  => 'Password',
                'Required' => true,
                'Note'     => "Must be at least 8 characters in length.Must include at least one letter and one number. Standard special characters (such as '!' '&' and '+') are optional. Dont use '¡','¿','¨','@',]",
            ],
        ];
    }

    public function registerAccount(array $fields)
    {
        $this->logger->debug(var_export($fields, true), ['pre' => true]);
        $this->checkFields($fields);

        $this->fields = $fields;

        $this->http->GetURL('https://www.virginatlantic.com/flying-club/join');

        if (!$this->waitForElement(WebDriverBy::xpath('//input[@id="email"]'), 30)) {
            throw new \CheckException("Page nol loaded");
        }

        sleep(10);

        $this->browser = new \HttpBrowser("none", new \CurlDriver());

        $this->browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
        $this->browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
        $this->browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));

        $this->http->brotherBrowser($this->browser);
        $cookies = $this->driver->manage()->getCookies();

        foreach ($cookies as $cookie) {
            $this->browser->setCookie($cookie['name'], $cookie['value'], $cookie['domain'], $cookie['path'],
                $cookie['expiry'] ?? null);
        }
        $this->http->cleanup();

        $this->runVerificationEmail();

        return $this->parseQuestion();
    }

    public function ProcessStep($step)
    {
        $this->logger->notice(__METHOD__);
        $answer = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);

        $payload = '{"operationName":"AccountOTPValidate","variables":{"otp":' . $answer . ',"emailId":"' . $this->fields['Email'] . '"},"query":"mutation AccountOTPValidate($otp: Int!, $emailId: String) {\n  accountOTPValidate(otp: $otp, emailId: $emailId)\n}"}';

        $this->browser->PostURL(self::URL, $payload, $this->headers);

        if ($this->browser->Response['code'] != 200) {
            throw new CheckException('Block (Try again)');
        }

        $response = $this->browser->JsonLog(null, 0, true);

        if (stripos($response['data']['accountOTPValidate'], 'Validated successfully') == false) {
            throw new CheckException('Block (Try again)');
        }

        if ($this->runCreateAccount()) {
            $response = $this->browser->JsonLog(null, 0, true);

            $this->ErrorMessage = json_encode([
                "status"       => "success",
                "message"      => "Registration is successful!",
                "login"        => $response['data']['accountEnrolMember']['memberDetails']['memberNumber'],
                "login2"       => '',
                "login3"       => '',
                "password"     => $this->fields["Password"],
                "email"        => $this->fields["Email"],
                "registerInfo" => [
                    [
                        "key"   => "FirstName",
                        "value" => $this->fields["FirstName"],
                    ],
                    [
                        "key"   => "LastName",
                        "value" => $this->fields["LastName"],
                    ],
                    [
                        "key"   => "BirthdayDate",
                        "value" => $this->fields["BirthdayDate"],
                    ],
                    [
                        "key"   => "Gender",
                        "value" => $this->fields['Gender'],
                    ],
                    [
                        "key"   => "Country",
                        "value" => $this->fields["Country"],
                    ],
                    [
                        "key"   => "Address",
                        "value" => $this->fields["Address"],
                    ],
                    [
                        "key"   => "City",
                        "value" => $this->fields["City"],
                    ],
                    [
                        "key"   => "State",
                        "value" => $this->fields["State"],
                    ],
                    [
                        "key"   => "ZipCode",
                        "value" => $this->fields["ZipCode"],
                    ],
                ],
                "active" => true,
            ], JSON_PRETTY_PRINT);
        }

        $this->browser->cleanup();

        return true;
    }

    protected function checkFields(&$fields): void
    {
        if (!filter_var($fields['Email'], FILTER_VALIDATE_EMAIL)) {
            throw new \UserInputError('Email address contains an incorrect format');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['FirstName'])) {
            throw new \UserInputError('First Name contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['LastName'])) {
            throw new \UserInputError('Last Name contains an incorrect symbol');
        }

        if ((strlen($fields['Password']) < 8 || strlen($fields['Password']) > 20)
            || !preg_match("/[a-z]/", $fields['Password'])
            || !preg_match("/[0-9]/", $fields['Password']) !== false
            || preg_match("/[%&¡@¿¨]/", $fields['Password'])) {
            throw new \UserInputError("Must be at least 8 characters in length.Must include at least one letter and one number. Standard special characters (such as '!' '&' and '+') are optional. Dont use '¡','¿','¨']");
        }

        if (preg_match("/[*¡!?¿<>\\ºª|\/\·@$%&№,;=?¿())_+{}\[\]\"\^€\$£]/", $fields['Address'])) {
            throw new \UserInputError('Address Line contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@$%&№,;=?¿())_+{}\[\]\"\^€\$£]/", $fields['City'])) {
            throw new \UserInputError('City contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@$%&№,;=?¿())_+{}\[\]\"\^€\$£]/", $fields['State'])) {
            throw new \UserInputError('State contains an incorrect symbol');
        }

        if (preg_match("/[*¡!?¿<>\\ºª|\/\·@$%&№,;=?¿_+{}\[\]\"\^€\$£]/", $fields['ZipCode'])) {
            throw new \UserInputError('Zip Code contains an incorrect symbol');
        }
    }

    private function parseQuestion()
    {
        $this->logger->notice(__METHOD__);
        $question = $this->browser->JsonLog(null, 0, true);

        $question = $question['data']['accountOTPGenerate'];

        $this->AskQuestion($question, null, "Question");

        return false;
    }

    private function runVerificationEmail(): void
    {
        $this->logger->notice(__METHOD__);
        $payload = '{"operationName":"AccountOTPGenerate","variables":{"emailId":"' . $this->fields['Email'] . '"},"query":"mutation AccountOTPGenerate($emailId: String) {\n  accountOTPGenerate(emailId: $emailId)\n}"}';

        $this->browser->PostURL(self::URL, $payload, $this->headers);

        if ($this->browser->Response['code'] != 200) {
            throw new CheckException('Block (Try again)');
        }
    }

    private function runCreateAccount(): bool
    {
        $this->logger->notice(__METHOD__);

        $states = $this->getLocation();
        $state = $states["achome.location.province.{$this->fields['Country']}_{$this->fields['State']}"];

        $date = new DateTime($this->fields['BirthdayDate'], new DateTimeZone('UTC'));
        $birthDate = $date->format('Y-m-d');
        $address = $this->splitAddress($this->fields["Address"]);

        $payload = '{"operationName":"AccountEnrolMember","variables":{"request":{"channelUserId":"","channelId":"","customerDetails":{"firstName":"' . $this->fields["FirstName"] . '","lastName":"' . $this->fields["LastName"] . '","dateOfBirth":"' . $birthDate . '","gender":"' . $this->genderIdentification($this->fields["Gender"]) . '","password":"' . $this->fields["Password"] . '","customerType":"Loyalty","addressDetails":[{"addressLine1":"' . $address[0] . '","addressLine2":"' . $address[1] . '","city":"' . $this->fields['City'] . '","postalCode":"' . $this->fields['ZipCode'] . '","region":"' . $state . '","country":"' . $this->fields['Country'] . '","addressVerified":true,"addressType":"Home","preferredFlag":"Y"}],"contactDetails":{"emailDetails":[{"usage":"Personal","emailId":"' . $this->fields['Email'] . '","preferredFlag":"Y"}],"phoneDetails":[{"phoneType":"Mobile","usage":"Personal","countryCode":"1","phoneNumber":"' . $this->fields['PhoneNumber'] . '","preferredFlag":"Y"}]},"loyaltyMember":{"memberType":"Individual","mediaCode":"WEB","enrollmentChannel":"WEB-MYA","termsAndConditionsAccepted":true},"preferences":[{"preferenceType":"VaaMarketingPermissions","preferenceValue":"N"},{"preferenceType":"VaPartnersMarketingPermissions","preferenceValue":"N"},{"preferenceType":"VaGroupMarketingPermissions","preferenceValue":"N"}]}}},"query":"mutation AccountEnrolMember($request: CreateCustomerAccountRequestInput) {\n  accountEnrolMember(request: $request) {\n    customerId\n    memberDetails {\n      memberNumber\n      __typename\n    }\n    __typename\n  }\n}"}';

        $this->browser->PostURL(self::URL, $payload, $this->headers);

        if ($this->browser->Response['code'] != 200) {
            throw new CheckException('Block (Try again)');
        }

        $response = $this->browser->JsonLog(null, 0, true);

        if (empty($response['data']['accountEnrolMember']['memberDetails']['memberNumber'])) {
            throw new CheckException('Account not created. Block (Try again)');
        }

        return true;
    }

    private function genderIdentification(string $gender): string
    {
        switch ($gender) {
            case 'male':
                return 'M';

            case 'female':
                return 'F';

            default:
                return 'Unspecified';
        }
    }

    private function splitAddress(string $address): array
    {
        if (strlen($address) > 20) {
            $middle = floor(strlen($address) / 2);
            $leftSpace = strrpos(substr($address, 0, $middle), ' ');
            $rightSpace = strpos($address, ' ', $middle);

            if ($leftSpace === false) {
                $splitPos = $rightSpace;
            } elseif ($rightSpace === false) {
                $splitPos = $leftSpace;
            } else {
                $splitPos = ($middle - $leftSpace <= $rightSpace - $middle) ? $leftSpace : $rightSpace;
            }

            if ($splitPos === false) {
                $splitPos = $middle;
            }

            $line1 = substr($address, 0, $splitPos);
            $line2 = substr($address, $splitPos + 1);

            return [$line1, $line2];
        }

        return [$address, ''];
    }

    private function getLocation(): array
    {
        $this->logger->notice(__METHOD__);
        $browser2 = new \HttpBrowser("none", new \CurlDriver());

        $browser2->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
        $browser2->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
        $browser2->setUserAgent($this->http->getDefaultHeader("User-Agent"));
        $this->http->brotherBrowser($browser2);

        $browser2->GetURL('https://kilo-content.aircanada.com/ac/applications/ac-common-idv/content/1.0.13/en-CA.json');
        $data = $browser2->JsonLog(null, 0, true);

        $browser2->cleanup();

        return $data;
    }
}
