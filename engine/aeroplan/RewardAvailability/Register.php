<?php

namespace AwardWallet\Engine\aeroplan\RewardAvailability;

use AwardWallet\Engine\ProxyList;
use Cache;
use WebDriverBy;

class Register extends \TAccountChecker
{
    use ProxyList;
    use \SeleniumCheckerHelper;

    public const MIN_PASSWORD_LENGTH = 10;
    private $registerInfo;

    private $fields;

    private $aeroplanNumber;

    public function InitBrowser()
    {
        \TAccountChecker::InitBrowser();
        $this->http->setHttp2(true);
        $this->UseSelenium();
        $this->keepCookies(false);

//        $this->useCamoufox();
        $this->useFirefoxPlaywright(\SeleniumFinderRequest::FIREFOX_PLAYWRIGHT_101);
//        $this->useFirefox();
//        $this->useChromePuppeteer();

        $this->seleniumRequest->setOs(\SeleniumFinderRequest::OS_LINUX);

        $this->seleniumOptions->addHideSeleniumExtension = false;
        $this->seleniumOptions->addPuppeteerStealthExtension = true;
//        $this->seleniumOptions->userAgent = null;

        $this->usePacFile(false);
//        $this->http->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/569.36 (KHTML, like Gecko) Safari/12.0 Safari/605.1.15');

//        $this->setProxyBrightData();

        $this->http->saveScreenshots = true;
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
                'Note'     => "Must be at least 10 characters in length.Must include at least one letter and one number. Standard special characters (such as '!' '&' and '+') are optional. Dont use '¡','¿','¨']",
            ],
        ];
    }

    public function registerAccount(array $fields)
    {
        $this->logger->debug(var_export($fields, true), ['pre' => true]);
        $this->checkFields($fields);

        $this->fields = $fields;

        $location = $this->getLocation();

        try {
            $this->http->GetURL('https://www.aircanada.com/aeroplan/member/enrolment?lang=en-US');
            $this->driver->manage()->window()->maximize();

            $closeMenu = $this->waitForElement(WebDriverBy::xpath('//button[@data-analytics-val="collapse side nav"]'), 30);

            $closeMenu->click();

            $email = $this->waitForElement(WebDriverBy::xpath('//input[@id="emailFocus"]'), 0);
            $pass = $this->waitForElement(WebDriverBy::xpath('//input[@id="pwd"]'), 0);

            if (!$email || !$pass) {
                $this->ErrorMessage = 'Page not loaded, Try again';

                return false;
            }

            $email->sendKeys($fields['Email']);
            $this->saveResponse();

            $pass->sendKeys($fields['Password']);
            $this->saveResponse();

            if ($cookieBtn = $this->waitForElement(WebDriverBy::xpath('//button[@id="onetrust-accept-btn-handler"]'), 5)) {
                $cookieBtn->click();
            }

            $this->driver->executeScript(/** @lang JavaScript */ "document.getElementById('checkBox-input').click();");
            $this->saveResponse();

            $this->driver->executeScript(/** @lang JavaScript */ '
                const buttons = document.querySelectorAll("button");

                buttons.forEach(button => {
                    if (button.textContent.trim() === "Continue") {
                        button.click();
                    }
                });
            ');

            $this->waitForElement(WebDriverBy::xpath('//input[@name="firstName"]'), 20);

            $date = new \DateTime($fields['BirthdayDate'], new \DateTimeZone('UTC'));

            $this->saveResponse();

//            $this->driver->executeScript("window.scrollBy(0, 300)");
//            $this->logger->debug('scroll');

            $firstName = $this->waitForElement(WebDriverBy::xpath('//input[@name="firstName"]'), 0);
            $lastName = $this->waitForElement(WebDriverBy::xpath('//input[@name="lastName"]'), 0);
            $gender = $this->waitForElement(WebDriverBy::xpath('//mat-select[@name="gender"]'), 0);
            $dayOfBird = $this->waitForElement(WebDriverBy::xpath('//mat-select[@formcontrolname="d"]'), 0);
            $mountOfBird = $this->waitForElement(WebDriverBy::xpath('//mat-select[@formcontrolname="m"]'), 0);
            $yearOfBird = $this->waitForElement(WebDriverBy::xpath('//mat-select[@formcontrolname="y"]'), 0);

            if (!$firstName || !$lastName || !$gender || !$dayOfBird || !$mountOfBird || !$yearOfBird) {
                $this->ErrorMessage = 'Page not loaded, Try again';

                return false;
            }

            $firstName->sendKeys($fields['FirstName']);
            $lastName->sendKeys($fields['LastName']);

            $gender->click();
            $genderOption = $this->waitForElement(WebDriverBy::xpath('//mat-option/span[contains(., "' . ucfirst($fields['Gender']) . '")]'), 3);
            $genderOption->click();
            $this->saveResponse();

            $dayOfBird->click();
            $this->driver->executeScript(/** @lang JavaScript */ 'document.evaluate("(//div[contains(@aria-label, \"Day\")]/mat-option[contains(., \"' . (int) $date->format('d') . '\")])[1]", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();');
            $this->saveResponse();

            $mountOfBird->click();
            $this->driver->executeScript(/** @lang JavaScript */ 'document.evaluate("(//div[contains(@aria-label, \"Month\")]/mat-option[contains(., \"' . $date->format('M') . '\")])[1]", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();');
            $this->saveResponse();

            $yearOfBird->click();
            $this->driver->executeScript(/** @lang JavaScript */ 'document.evaluate("(//div[contains(@aria-label, \"Year\")]/mat-option[contains(., \"' . $date->format('Y') . '\")])[1]", document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue.click();');
            $this->saveResponse();

            $continueButton = $this->waitForElement(WebDriverBy::xpath('//button[contains(., "Continue ")]'), 0);
            $continueButton->click();

            $addressLine = $this->waitForElement(WebDriverBy::xpath('//input[@formcontrolname="addressLine1"]'), 20);

            $this->saveResponse();

            $country = $this->waitForElement(WebDriverBy::xpath('//mat-select[@name="country"]'), 3);

            if (!$country || !$addressLine) {
                $this->saveResponse();
                $this->ErrorMessage = 'Page not loaded, Try again';

                return false;
            }

            $country->click();
            sleep(3);
            $this->saveResponse();  // бывает очень долго не открывает
            $countryStr = $location["achome.location.country.{$fields['Country']}"];
            $countryOption = $this->waitForElement(WebDriverBy::xpath('(//mat-option/span[contains(., " ' . $countryStr . '")])[1]'), 10);

            if (!$countryOption) {
                $addressLine->click();
                $country->click();
                $countryOption = $this->waitForElement(WebDriverBy::xpath('(//mat-option/span[contains(., " ' . $countryStr . '")])[1]'), 5);

                if (!$countryOption) {
                    throw new \CheckException('Country list not loaded. Try again');
                }
            }
            $countryOption->click();
            $this->saveResponse();

            $stateSelect = $this->waitForElement(WebDriverBy::xpath('//mat-select[@name="state"]'), 0);
            $ZipCode = $this->waitForElement(WebDriverBy::xpath('//input[@name="zip"]'), 0);
            $city = $this->waitForElement(WebDriverBy::xpath('//input[@name="city"]'), 0);
            $phoneCode = $this->waitForElement(WebDriverBy::xpath('//mat-select[@formcontrolname="countryCode"]'), 0);

            if (!$stateSelect || !$ZipCode || !$city || !$phoneCode) {
                $this->ErrorMessage = 'Page not loaded, Try again';

                return false;
            }

            $stateSelect->click();

            $state = $location["achome.location.province.{$fields['Country']}_{$fields['State']}"];
            $stateOption = $this->waitForElement(WebDriverBy::xpath('(//mat-option/span[contains(., "' . $state . '")])[1]'), 3);
            $stateOption->click();
            $this->saveResponse();

            $city->sendKeys($fields['City']);
            $this->saveResponse();

            $addressLine->sendKeys($fields['Address']);
            $this->saveResponse();

            $ZipCode->sendKeys($fields['ZipCode']);
            $this->saveResponse();

            $phoneCode->click();
            sleep(3);
            $phoneCodeOption = $this->waitForElement(WebDriverBy::xpath('(//mat-option//span[text()="' . $countryStr . '"])[1]'), 3);
            $phoneCodeOption->click();
            $this->saveResponse();

            $phoneNumber = $this->waitForElement(WebDriverBy::xpath('//input[@name="pnumber"]'), 0);
            $phoneNumber->sendKeys($fields['PhoneNumber']);
            $this->saveResponse();
            $city->click();

            $this->driver->executeScript(/** @lang JavaScript */ "document.getElementById('privacyPolicycheckBox').click();");

            $this->driver->executeScript(/** @lang JavaScript */ "document.getElementById('kilo-recaptcha').scrollIntoView({ behavior: 'smooth', block: 'center' }); ");
            $this->logger->error('Scroll');
            $this->saveResponse();

            $this->validateCaptcha();

            $this->driver->executeScript(/** @lang JavaScript */ '
                const buttons = document.querySelectorAll("button");

                buttons.forEach(button => {
                    if (button.textContent.trim() === "Create my account") {
                        button.click();
                    }
                });
            ');

            if ($this->waitForElement(WebDriverBy::xpath('//kilo-email-verification-pres[@id="undefinedEmailVerification"]'), 30)) {
                $this->getRegisterInfo();
                $this->saveResponse();

                $this->aeroplanNumber = $this->http->FindSingleNode('//span[contains(@class, "aeroplan-number")]');
                $this->aeroplanNumber = preg_replace('/\D/', '', $this->aeroplanNumber);

                if (!$this->parseQuestion()) {
                    return false;
                } else {
                    $this->ErrorMessage = json_encode([
                        "status"       => "success",
                        "message"      => "The account has been created. You need to go through email verification on the website.",
                        "login"        => $this->aeroplanNumber,
                        "login2"       => '',
                        "login3"       => '',
                        "password"     => $fields["Password"],
                        "email"        => $fields["Email"],
                        "registerInfo" => $this->registerInfo,
                        "active"       => false,
                    ], JSON_PRETTY_PRINT);
                }

                return true;
            }

            $this->saveResponse();

            if ($m = $this->http->FindSingleNode('//mat-error | //div[@class="content"][contains(., "Please try again")]')) {
                $this->logger->error($m);

                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }

    public function ProcessStep($step)
    {
        $this->logger->debug(__METHOD__);

        $verificationLink = $this->Answers[$this->Question];
        unset($this->Answers[$this->Question]);

        $this->http->GetURL($verificationLink);

        $success = $this->waitForElement(\WebDriverBy::xpath("//h1[contains(., 'Welcome to Aeroplan!')]"));

        if ($success) {
            $this->ErrorMessage = json_encode([
                "status"       => "success",
                "message"      => "Registration is successful!",
                "login"        => $this->aeroplanNumber,
                "login2"       => '',
                "login3"       => '',
                "password"     => $this->fields["Password"],
                "email"        => $this->fields["Email"],
                "registerInfo" => $this->registerInfo,
                "active"       => true,
            ], JSON_PRETTY_PRINT);

            return true;
        } else {
            // Go to Email for verification
            $this->ErrorMessage = json_encode([
                "status"       => "success",
                "message"      => "The account has been created. You need to go through email verification on the website.",
                "login"        => $this->aeroplanNumber,
                "login2"       => '',
                "login3"       => '',
                "password"     => $this->fields["Password"],
                "email"        => $this->fields["Email"],
                "registerInfo" => $this->registerInfo,
                "active"       => false,
            ], JSON_PRETTY_PRINT);

            return true;
        }
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

        if ((strlen($fields['Password']) < 10 || strlen($fields['Password']) > 30)
            || !preg_match("/[a-z]/", $fields['Password'])
            || !preg_match("/[A-Z]/", $fields['Password'])
            || !preg_match("/[0-9]/", $fields['Password']) !== false
            || preg_match("/[%&¡¿¨]/", $fields['Password'])) {
            throw new \UserInputError("Must be at least 8 characters in length.Must include at least one letter and one number. Standard special characters (such as '!' '&' and '+') are optional. Dont use '¡','¿','¨']");
        }

        if (preg_match("/[*¡!?¿<>\\ºª|\/\·@$%&№,;=?¿())_+{}\[\]\"\^€\$£]/", $fields['Address'])) {
            throw new \UserInputError('Address Line contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['City'])) {
            throw new \UserInputError('City contains an incorrect symbol');
        }

        if (preg_match("/[\d*¡!?¿<>\\ºª|\/\·@#$%&.,;=?¿())_+{}\-\[\]\"\^€\$£']/", $fields['State'])) {
            throw new \UserInputError('State contains an incorrect symbol');
        }

        if (preg_match("/[*¡!?¿<>\\ºª|\/\·@$%&№,;=?¿_+{}\[\]\"\^€\$£]/", $fields['ZipCode'])) {
            throw new \UserInputError('Zip Code contains an incorrect symbol');
        }
    }

    protected function parseReCaptcha($key = null)
    {
        $this->logger->notice(__METHOD__);

        if (!$key) {
//            $key = $this->http->FindSingleNode('//iframe[@title="reCAPTCHA"]/@src', null, true, "/\&k=([^\&]+)/");
            $key = '6LdFYr8UAAAAAFqVHp4JSiMBepIV39gAseHox7Sy';
        }
        $this->logger->debug("data-sitekey: {$key}");

        $method = "userrecaptcha";

        if (!$key) {
            return false;
        }
        $this->recognizer = $this->getCaptchaRecognizer();
        $this->recognizer->RecognizeTimeout = 120;
        $parameters = [
            "method"  => $method,
            "pageurl" => $this->http->currentUrl(),
            "proxy"   => $this->http->GetProxy(),
        ];

        return $this->recognizeByRuCaptcha($this->recognizer, $key, $parameters);
    }

    private function parseQuestion(): bool
    {
        $this->logger->notice(__METHOD__);

        if ($this->http->FindSingleNode("//p[contains(., 'We’ve sent a verification email to {$this->fields['Email']}')]")) {
            $question = "We’ve sent a verification email to {$this->fields['Email']}";

            $this->State['email'] = $this->fields['Email'];

            $this->holdSession();
            $this->AskQuestion($question, null, "Question");

            return false;
        }

        return true;
    }

    private function getLocation(): array
    {
        $data = Cache::getInstance()->get('ra_ac_locations');

        if (!isset($data) || $data === false || !is_array($data)) {
            $browser = new \HttpBrowser("none", new \CurlDriver());

            $browser->SetProxy("{$this->http->getProxyAddress()}:{$this->http->getProxyPort()}");
            $browser->setProxyAuth($this->http->getProxyLogin(), $this->http->getProxyPassword());
            $browser->setUserAgent($this->http->getDefaultHeader("User-Agent"));
            $this->http->brotherBrowser($browser);

            $browser->GetURL('https://kilo-content.aircanada.com/ac/applications/ac-common-idv/content/1.0.13/en-CA.json');
            $data = $browser->JsonLog(null, 0, true);

            Cache::getInstance()->set('ra_ac_locations', $data, 60 * 60 * 24 * 7);

            $browser->cleanup();
        }

        return $data;
    }

    private function validateCaptcha(): void
    {
        $captcha = $this->parseReCaptcha();

        $this->saveResponse();

        if ($captcha === false) {
            throw new \CheckException("Captcha error. Checked!");
        }

        $this->driver->executeScript('document.getElementsByName("g-recaptcha-response").value = "' . $captcha . '";');
        $this->saveResponse();

        $this->logger->notice("Executing captcha callback");
        $this->driver->executeScript('
            var findCb = (object) => {
                if (!!object["callback"] && !!object["sitekey"]) {
                    return object["callback"]
                } else {
                    for (let key in object) {
                        if (typeof object[key] == "object") {
                            return findCb(object[key])
                        } else {
                            return null
                        }
                    }
                }
            }
            findCb(___grecaptcha_cfg.clients[0])("' . $captcha . '")
        ');

//        if ($iframe = $this->waitForElement(\WebDriverBy::xpath('//iframe[@title="reCAPTCHA"]'), 10)) {
//            $this->driver->switchTo()->frame($iframe);
//
//            $captcha = $this->waitForElement(\WebDriverBy::xpath('//span[@id="recaptcha-anchor"]'), 5);
//
//            $captcha->click();
//            $this->driver->switchTo()->defaultContent();
//
//            sleep(5);
//            $this->saveResponse();
//        }

        sleep(5);
    }

    private function getRegisterInfo(): void
    {
        $this->logger->notice(__METHOD__);

        $newEnrolment = $this->driver->executeScript("return sessionStorage.getItem('newEnrolment');");
        $newEnrolment = $this->http->JsonLog($newEnrolment, 0, true);

        if (empty($newEnrolment)) {
            $this->logger->error('Register info not found...');

            return;
        }

        $regInfo = [
            $newEnrolment["personalInfo"],
            $newEnrolment["contactInfo"]["address"],
            $newEnrolment["enrol"],
        ];

        $phoneNumber = $newEnrolment["contactInfo"]["phone"]["countryDigit"] . $newEnrolment["contactInfo"]["phone"]["phoneNumber"];

        $excludedKeys = ['middleName', 'language', 'password', 'addressLine2', 'countryCode', 'memberId', 'email'];

        foreach ($regInfo as $info) {
            foreach ($info as $key => $value) {
                if (in_array($key, $excludedKeys)) {
                    continue;
                } else {
                    if ($key === 'birthday') {
                        $birthday = new \DateTime($value, new \DateTimeZone('UTC'));
                        $this->registerInfo[] = [
                            'key'   => $key,
                            'value' => $birthday->format('d/m/Y'),
                        ];
                    } else {
                        $this->registerInfo[] = [
                            'key'   => $key,
                            'value' => $value,
                        ];
                    }
                }
            }
        }

        $this->registerInfo[] = [
            'key'   => 'phoneNumber',
            'value' => $phoneNumber,
        ];

        $this->logger->debug(var_export($this->registerInfo, true), ['pre' => true]);
    }
}
