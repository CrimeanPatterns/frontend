<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Commands;

use AwardWallet\Common\Selenium\FingerprintFactory;
use AwardWallet\Common\Selenium\FingerprintRequest;
use AwardWallet\Common\Selenium\SeleniumDriverFactory;
use AwardWallet\Engine\Settings;
use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\FrameworkExtension\Error\ErrorUtils;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\AppBot\Adapter\Slack;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckAffiliateDirectLinksCommand extends Command
{
    use \AwardWallet\Engine\ProxyList;

    public const DAYS_AFTER_DETECT_DATA = 3;
    public const DAYS_SUCCESS_CHECK_PAST = 14;

    public const VAR2_ARGS = [
        'awid' => 'testbot',
        'cid' => 'linktest',
        'rkbtyn' => 'emjot0rf10',
    ];

    private const IGNORE_SEARCH_BUTTON_APPLY_CARD_ID = []; // [215, 254, 255, 262];
    private const IGNORE_SEARCH_BUTTON_APPLY_AUTH_REQUIRED_CARD_ID = [125, 249, 259, 265, 266];
    private const IS_USE_PROXY = true;

    private const ERROR_FAILED_LOAD_DIRECT_LINK = 'Failed to load initial "DirectClickURL" link';
    private const ERROR_FOUND_APPLY_NOW_BUTTON = 'Could not find the [Apply Now] button';
    private const ERROR_FAILED_LOAD_APPLY_NOW_LINK = 'Failed to load page from [Apply Now] button';
    private const ERROR_FORM_NOT_FOUND = 'NOT FOUND Form to fill out the questionnaire';
    private const ERROR_POSSIBLE_SPECIAL_CONDITIONS = '[?] Special conditions are required for verification (sign in | location)';
    private const SUCCESS_FORM_FOUND = '[OK] Form for filling out the questionnaire found';

    private const TIMEOUT_PAGE_LOAD = 20;
    private const TIMEOUT_WAIT_ELEMENT_AFTER_LOAD = 3;

    private const DEBUG_CARD_ID = [];

    public static $defaultName = 'aw:credit-cards:check-affiliate-links';

    private EntityManagerInterface $entityManager;
    private \CurlDriver $curlDriver;
    private AppBot $appBot;
    private OutputInterface $output;
    private SeleniumDriverFactory $seleniumDriverFactory;
    private \RemoteWebDriver $webDriver;
    private FingerprintFactory $fingerprintFactory;
    private \SeleniumDriver $driver;

    private array $log = [];

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        SeleniumDriverFactory $seleniumDriverFactory,
        FingerprintFactory $fingerprintFactory,
        AppBot $appBot,
        \CurlDriver $curlDriver
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->seleniumDriverFactory = $seleniumDriverFactory;
        $this->fingerprintFactory = $fingerprintFactory;
        $this->appBot = $appBot;
        $this->curlDriver = $curlDriver;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Check Credit Card Affiliate Links Daily to Verify Tracking')
            ->addOption('cardId', null, InputOption::VALUE_OPTIONAL, 'Card ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->output = $output;

        $cards = $this->entityManager->getConnection()->fetchAllAssociative("
            SELECT CreditCardID, CardFullName, DirectClickURL, QsAffiliate
            FROM CreditCard
            WHERE 
                    QsAffiliate IN (?)
                AND DirectClickURL IS NOT NULL
                AND DirectClickURL <> ''
        ",
            [[CreditCard::QS_AFFILIATE_DIRECT, CreditCard::QS_AFFILIATE_CARDRATINGS]],
            [Connection::PARAM_INT_ARRAY]
        );

        $cards = array_column($cards, null, 'CreditCardID');

        if (!empty(self::DEBUG_CARD_ID)) {
            $tmp = [];

            foreach (self::DEBUG_CARD_ID as $id) {
                $tmp[$id] = $cards[$id];
            }
            $cards = $tmp;
        }

        if (!empty($optionCardId = (int) $input->getOption('cardId'))) {
            $cards = [$optionCardId => $cards[$optionCardId]];
        }

        $status = [];
        $this->driver = $this->getDriver();

        foreach ($cards as $card) {
            $this->driver->start();

            if (!$this->driver->isStarted()) {
                throw new \Exception('Failed connect to Selenium');
            }
            $this->webDriver = $this->driver->webDriver;

            $cardId = $card['CreditCardID'];
            $link = $card['DirectClickURL'];
            $link = StringHandler::var2TrackingModify($link, array_merge(self::VAR2_ARGS, [
                'mid' => time(),
            ]));

            $status[$cardId] = [
                'success' => false,
                'error' => 0,
                'card' => $card,
                'directUrl' => $link,
            ];

            $output->writeln(['', 'Card ' . $cardId . ': ' . $link]);

            $this->log[$cardId] = [];

            switch ((int) $card['QsAffiliate']) {
                case CreditCard::QS_AFFILIATE_CARDRATINGS:
                    $result = $this->checkByCardRatings_selenium($status[$cardId]);

                    break;

                case CreditCard::QS_AFFILIATE_DIRECT:
                    $result = $this->checkByDirect_selenium($status[$cardId]);

                    break;

                default:
                    throw new \RuntimeException('Unknown QsAffiliate');
            }

            if (true === $result) {
                $this->log[$cardId][] = self::SUCCESS_FORM_FOUND;
                $this->output->writeln(' #sucess');

                $result = [
                    'success' => true,
                ];

                $this->entityManager->getConnection()->update(
                    'CreditCard',
                    ['SuccessCheckDate' => date('Y-m-d H:i:s')],
                    ['CreditCardID' => $cardId]
                );
            }

            if (array_key_exists('error', $result)) {
                if (in_array($cardId, self::IGNORE_SEARCH_BUTTON_APPLY_CARD_ID)) {
                    $result = [
                        'error' => 40,
                        'message' => $this->getErrorMessage(40),
                    ];
                    $this->output->writeln(' #possible: ' . $result['message']);
                } elseif (in_array($cardId, self::IGNORE_SEARCH_BUTTON_APPLY_AUTH_REQUIRED_CARD_ID)) {
                    $result = [
                        'error' => 41,
                        'message' => $this->getErrorMessage(41),
                    ];
                    $this->output->writeln(' #possible: ' . $result['message']);
                } elseif (null !== ($error = $this->findPlannedErrors())) {
                    $result = [
                        'error' => 50,
                        'message' => $error,
                    ];
                    $this->output->writeln($result['message']);
                }

                $this->log[$cardId][] = $result['message'];
            }

            $status[$cardId] = array_merge($status[$cardId], $result);

            try {
                $this->driver->stop();
            } catch (\Exception $e) {
                $logEntry = ErrorUtils::makeLogEntry($e);
                $output->writeln(
                    "Error: " . $logEntry->getMessage() . '. Context: '
                    . \json_encode($logEntry->getContext(), JSON_PRETTY_PRINT)
                );
            }
        }

        $output->writeln('');

        $group = [
            'success' => [],
            'failure' => [],
        ];

        foreach ($status as $cardId => $state) {
            $key = $state['success'] ? 'success' : 'failure';
            $group[$key][] = $state;
        }

        // $this->showCardsWithoutLinks($cards);

        try {
            $message = $this->generateMessage($group);
            $this->appBot->send(Slack::CHANNEL_AW_STATS, $message);
        } catch (\Exception $e) {
            $this->logger->critical('CheckAffiliateDirectLinksCommand: slack ' . $e->getMessage(), $message);
        }

        foreach ($this->log as $cardId => $log) {
            $output->writeln($cardId . ': ' . $cards[$cardId]['CardFullName']);
            $output->writeln(array_unique($log));
            $output->writeln('');
        }

        $this->checkSuccessCardClicks();
    }

    /**
     * @return array|true
     */
    private function checkByDirect_selenium(array $statusCard)
    {
        $cardId = $statusCard['card']['CreditCardID'];
        $startLink = $applyLink = $statusCard['directUrl'];
        $this->log[$cardId][] = 'Open page by DirectURL: ' . $startLink;

        $this->webDriver->get($startLink);

        sleep(self::TIMEOUT_PAGE_LOAD);
        $applyBtn = $this->findApplyNowButton();
        $this->output->writeln('applyBtn: ' . (null === $applyBtn ? 'NOT found' : 'FOUND'));

        $isFormFounded = null === $applyBtn
            ? $this->checkFinalFormFilling()
            : null;

        if (null === $isFormFounded) {
            if (null === $applyBtn) {
                $result = [
                    'error' => 2,
                    'message' => $this->getErrorMessage(2),
                    'link' => $startLink,
                ];
                $this->log[$cardId][] = $result['message'];
                $this->output->writeln(' #fail: ' . $result['message']);

                return $result;
            }

            $applyLink = $applyBtn->getAttribute('href');
            $this->output->writeln('applyBtn href: ' . $applyLink);

            if (in_array($cardId, [132])) {
                $this->output->writeln('intermediate page...');
                $this->removeElementsTarget(true);
                sleep(1);

                if (false !== strpos($applyLink, 'http')) {
                    $this->output->writeln('get link...');
                    $this->webDriver->get($applyLink);
                } else {
                    $applyBtn->click();
                }

                sleep(self::TIMEOUT_PAGE_LOAD);
                $applyLink = null;
                $applyBtn = $this->findApplyNowButton();

                if (null !== $applyBtn) {
                    $applyLink = $applyBtn->getAttribute('href');
                }
            }

            if (null === $applyLink) {
                $result = [
                    'error' => 20,
                    'message' => $this->getErrorMessage(20),
                    'link' => $startLink,
                ];
                $this->log[$cardId][] = $result['message'];
                $this->output->writeln(' #fail: ' . $result['message']);

                return $result;
            }

            $this->removeElementsTarget();
            sleep(1);
            $applyBtn->click();

            $this->output->writeln('button [Apply Now] link : ' . $applyLink);
            $this->log[$cardId][] = 'Open [Apply Now] button URL ' . $applyLink;

            sleep(self::TIMEOUT_PAGE_LOAD);
            $applyBtn = null;

            $isFormFounded = $this->checkFinalFormFilling();
        }

        if (!$isFormFounded) {
            $result = [
                'error' => 10,
                'message' => $this->getErrorMessage(10),
                'link' => $applyLink,
            ];
            $this->log[$cardId][] = $result['message'];
            $this->output->writeln(' #fail: ' . $result['message']);

            return $result;
        }

        return true;
    }

    /**
     * @return array|true
     */
    private function checkByCardRatings_selenium(array $statusCard)
    {
        $cardId = $statusCard['card']['CreditCardID'];
        $startLink = $statusCard['directUrl'];
        $this->log[$cardId][] = 'Open page by CardRatingsURL: ' . $startLink;

        $this->webDriver->get($startLink);
        sleep(self::TIMEOUT_PAGE_LOAD);

        $applyBtn = $this->waitElement(
            \WebDriverBy::xpath("//a[@data-cfelement='apply_now_top'][contains(text(),'Apply Now')]"),
            self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
        );

        if (null === $applyBtn) {
            $applyBtn = $this->findApplyNowButton();
        }

        if (null === $applyBtn) {
            $result = [
                'error' => 2,
                'message' => $this->getErrorMessage(2),
                'link' => $startLink,
            ];
            $this->log[$cardId][] = $result['message'];
            $this->output->writeln(' #fail: ' . $result['message']);

            return $result;
        }

        $applyLink = $applyBtn->getAttribute('href');

        if (null === $applyLink) {
            $result = [
                'error' => 20,
                'message' => $this->getErrorMessage(20),
                'link' => $startLink,
            ];
            $this->log[$cardId][] = $result['message'];
            $this->output->writeln(' #fail: ' . $result['message']);

            return $result;
        }

        $this->removeElementsTarget();
        sleep(2);

        $applyBtn->click();
        sleep(self::TIMEOUT_PAGE_LOAD);
        $applyBtn = null;
        $this->log[$cardId][] = 'Open [Apply Now] button URL ' . $applyLink;

        // check extended [Apply Now] buttons

        $nextApplyBtn = $this->waitElement(
            \WebDriverBy::xpath('//a[contains(@class, "cta-button")][contains(text(), "Apply now")]'),
            self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
        );

        if (null === $nextApplyBtn) {
            $nextApplyBtn = $this->waitElement(
                \WebDriverBy::xpath('(//a[@data-initial-value="Apply Now"][contains(text(), "Apply Now")])[1]'),
                self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
            );
        }

        if (null === $nextApplyBtn) {
            $nextApplyBtn = $this->waitElement(
                \WebDriverBy::xpath('//a[contains(@class, "apply-now-link")][contains(text(), "Apply now")]'),
                self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
            );
        }
        /*
        if (null === $nextApplyBtn) {
            $nextApplyBtn = $this->waitElement(
                \WebDriverBy::xpath('//a[contains(@class, "usaa-hero-block-btn")][contains(text(), "Apply now")]'),
                self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
            );
        }
        if (null === $nextApplyBtn) {
            $nextApplyBtn = $this->waitElement(
                \WebDriverBy::xpath('//a[contains(@class, "btn")][contains(text(), "Apply now")]'),
                self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
            );
        }
        */

        if (null !== $nextApplyBtn) {
            $this->removeElementsTarget();
            sleep(2);
            $applyLink = $nextApplyBtn->getAttribute('href');
            $nextApplyBtn->click();
            sleep(self::TIMEOUT_PAGE_LOAD);
            $nextApplyBtn = null;
            $this->log[$cardId][] = 'Open next [Apply Now] button URL ' . $applyLink;
        }

        sleep(self::TIMEOUT_PAGE_LOAD);
        $isFormFounded = $this->checkFinalFormFilling();

        if (!$isFormFounded) {
            $result = [
                'error' => 10,
                'message' => $this->getErrorMessage(10),
                'link' => $applyLink,
            ];
            $this->log[$cardId][] = $result['message'];
            $this->output->writeln(' #fail: ' . $result['message']);

            return $result;
        }

        return true;
    }

    private function findApplyNowButton(): ?\RemoteWebElement
    {
        $paths = [
            "//a[@id='applynow_btn'][contains(text(),'Apply')]",
            "//div[@id='sticky_header_div']//a[contains(@class, 'sh-active-client')][contains(text(),'Apply')]",
            "//main[@id='main-content']//a[contains(@class, 'btn')][contains(@aria-label, 'Apply now')]",
            "//div[contains(@class, 'active')]//div[contains(@class, 'applynow')]//a[contains(@class, 'chaseanalytics-track-link')][contains(text(), 'Click here to')]",
            "(//a[contains(@class, 'chaseanalytics-track-link')][contains(text(),'Apply now')])[1]",
            "//a[contains(@aria-label, 'Apply now for')][contains(text(),'Apply now')]",
            "//div[contains(@class, 'header-applynow-wrapper')]//a[contains(@class, 'applynow-button')]",
            "//a[@data-lh-name='ApplyNow']",
            '//a[@data-lh-name="ApplyNowLink"]',
            '//a[@data-pt-name="cc_apply_now"]',
            '//button[@id="pdp-hero-apply-now"]',
            "//button[contains(@title, 'Click here to apply')]",
            '//a[@data-track="js_foc_al_apply"]',
            "//a[contains(@class, 'apply-button')][contains(text(),'Apply now')]",
            "//a[@data-lh-name='ApplyGuest'][@data-track='hero_apply_guest_c']",
            "//a[@data-lh-name='ApplyNow'][@data-track='top_apply_button']",
            "//a[@data-lh-name='ApplyNow'][contains(text(),'APPLY AS A GUEST')]",
            "//a[@data-lh-name='applyNow'][contains(text(),'APPLY AS A GUEST')]",
            "//a[@data-lh-name='ApplyNowLink'][contains(text(),'APPLY NOW')]",
            "//div[@data-qe-id='GroupedButtons']//a[contains(text(),'Apply Now')]",
            "//div[contains(@class, 'apply-now')]//a[contains(@class, 'applynow')][contains(text(),'Apply Now')]",
            "//a[@data-pt-name='hd_apply_now'][contains(text(),'Apply as guest')]",
            "//a[@data-cfelement='apply_now_top'][contains(text(),'Apply Now')]",
            "(//p[contains(text(), 'Not a cardmember yet? Click')]//a[contains(@class, 'links')][contains(text(), 'here')])[1]",
        ];

        /*
        ## group
        $groupXPath = [];
        foreach ($paths as $path) {
            $groupXPath[] .= '(' . $path . ')';
        }
        $groupXPath = '(' . implode(' | ', $groupXPath) . ')[1]';

        echo PHP_EOL . $groupXPath . PHP_EOL;

        $applyBtn = $this->waitElement(
            \WebDriverBy::xpath($groupXPath),
            20//self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
        );
        // group
        */

        foreach ($paths as $path) {
            $applyBtn = $this->waitElement(
                \WebDriverBy::xpath('(' . $path . ')[1]'),
                self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
            );

            if (null !== $applyBtn) {
                break;
            }
        }

        if (null === $applyBtn) {
            $applyBtn = $this->waitElement(
                \WebDriverBy::xpath("//div[@data-qe-id='GroupedButtons']//button[@data-qe-id='Button'][contains(text(),'Apply Now')]"),
                self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
            );

            if (null !== $applyBtn) {
                $this->removeElementsTarget();
                sleep(1);

                return $applyBtn;
            }
        }

        if (null === $applyBtn) {
            $collapseBtn = $this->waitElement(
                \WebDriverBy::xpath('//button[@data-track="js_foc_open"]'),
                self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
            );

            if (null !== $collapseBtn) {
                $collapseBtn->click();
                sleep(2);

                $extBtn = $this->waitElement(
                    \WebDriverBy::xpath('(//a[@data-track="js_foc_al_apply"])[1]'),
                    self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD
                );

                $collapseBtn = null;

                if (null !== $extBtn) {
                    return $extBtn;
                }
            }
        }

        return $applyBtn;
    }

    private function checkFinalFormFilling(): bool
    {
        $isFormFounded = $this->isFoundFieldSet([
            '//*[contains(text(), "You\'re applying for")]',
            '//mds-text-input[@id="applicant-firstName"]',
            '//mds-button[@id="primary-nav-button"]',
        ]);

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                "//span[contains(text(), 'Personal Information')]",
                '//input[@id="firstName"]',
                '//input[@id="lastName"]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                "//h2[contains(text(), 'Enter your Business Information')]",
                '(//input[@id="business-name-41"] | //input[@title="Legal Business Name"])[last()]',
                '//button[contains(@class, "dls-icon-lock-on-submit")]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                "//span[contains(@class, 'glassbox-dom-unmask')][contains(text(), 'Personal Information')]",
                '//input[@id="firstName"]',
                '//button[contains(@class, "grv-button")][contains(text(), "Continue")]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                "//h3[contains(text(), 'Complete your application and get a response')]",
                '//input[@id="customerFirstName"]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                "//p[contains(text(), 'What’s your legal name?')]",
                '//input[@id="FIRST_NAME"]',
                "//button[contains(@class, 'offer-btn')]//div[contains(text(), 'Continue pre-approval')]",
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                "//span[contains(text(), 'start your application')]",
                '//input[@id="restrictedFirstName"]',
                '//button[@data-tracking-ref="WFFormSubmitButton-button-"][contains(text(), "Submit")]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                "//div[contains(text(), 'your legal name?')]",
                '//input[@id="firstName"]',
                '//button[contains(@class, "grv-button")][contains(text(), "Continue")]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                "//h3[contains(text(), 'Personal info')]",
                '//input[@name="firstName"]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                '//h1[contains(text(), "get started")]',
                '//input[@name="firstName"]',
                '//mds-button[@id="SUBMIT-nav-ctr-btn"]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                '//h1[contains(text(), "get started")]',
                '//mds-text-input[@id="applicant-firstName"]',
                '//mds-button[@id="primary-nav-button"]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                '//h1[contains(text(), "get started")] or //*[contains(text(), "You\'re applying for")]',
                '//mds-text-input[@id="applicant-firstName"]',
                '//mds-button[@id="primary-nav-button"]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                '//h2[contains(text(), "Personal Information")]',
                '//input[@id="personalInfo.fullName.firstName"]',
                '//button[@data-testid="continueButton"][contains(text(), "Continue to Terms")]',
            ]);
        }

        if (!$isFormFounded) {
            $isFormFounded = $this->isFoundFieldSet([
                '//input[@aria-label="Email Address"]',
                '//button[@id="submit"][contains(text(), "Submit Application")]',
            ]);
        }

        return $isFormFounded;
    }

    private function removeElementsTarget($isRemove = false): void
    {
        if ($isRemove) {
            $this->webDriver->executeScript('
                var _elements = document.querySelectorAll("a[target]");
                for (var i = -1; ++i < _elements.length;) { 
                    _elements.item(i).removeAttribute("target");
                }
            ');
        } else {
            $this->webDriver->executeScript('
                var _elements = document.querySelectorAll("a[target]");
                for (var i = -1; ++i < _elements.length;) { 
                    /*_elements.item(i).removeAttribute("target");*/
                    _elements.item(i).setAttribute("target", "_self");
                }
            ');
        }
    }

    private function isFoundFieldSet(array $fields): bool
    {
        $founded = [];

        foreach ($fields as $field) {
            $element = $this->waitElement(\WebDriverBy::xpath($field), self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD);
            $founded[] = null !== $element;
            $element = null;
        }

        if (1 === count(array_unique($founded)) && true === $founded[0]) {
            return true;
        }

        return false;
    }

    private function findPlannedErrors(): ?string
    {
        $paths = [
            '//*[contains(text(), "having trouble finding the page you")]',
            '//*[contains(text(), "is currently unavailable")]',
            '//*[contains(text(), "offer you were just viewing may be")]',
            '//*[contains(text(), "HTTP ERROR")]',
            '//*[contains(text(), "If you are in the market for a new credit card, choose from")]',
            '//*[contains(text(), "Sorry! It looks like the credit card you were interested in is no longer available.")]',
        ];

        foreach ($paths as $path) {
            $error = $this->waitElement(\WebDriverBy::xpath($path), self::TIMEOUT_WAIT_ELEMENT_AFTER_LOAD);

            if (null !== $error) {
                $text = strip_tags($error->getText());
                $text = str_replace(["\r", "\n"], ' ', $text);
                $text = preg_replace('/\s+/', ' ', $text);
                $error = null;

                return $text;
            }
        }

        return null;
    }

    private function generateMessage(array $group): array
    {
        $message['blocks'] = [];
        $message['blocks'][] = [
            'type' => 'header',
            'text' =>
                [
                    'type' => 'plain_text',
                    'text' => 'Results of checking affiliate links to cards',
                ],
        ];

        if (empty($group['failure'])) {
            $message['blocks'][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'plain_text',
                    'text' => count($group['success']) . ' cards successfully verified',
                    'emoji' => true,
                ],
            ];
        } else {
            foreach ($group['failure'] as $state) {
                $error = empty($state['message'])
                    ? $this->getErrorMessage((int) $state['error'])
                    : $state['message'];
                $error = trim($error);

                $text = $state['card']['CardFullName'] . ' link test failed ' . date('m/d/Y H:i') . ' UTC'
                    . PHP_EOL
                    . (50 === $state['error'] ? '> ' : '')
                    . '<https://awardwallet.com/manager/edit.php?Schema=CreditCard&ID=' . $state['card']['CreditCardID'] . '|' . $error . '>';

                $message['blocks'][] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $text,
                    ],
                ];
            }

            // ----

            $message['blocks'][] = ['type' => 'divider'];
            $message['blocks'][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => count($group['success']) . ' cards successfully verified',
                ],
            ];
        }

        /*
        $success = [];
        foreach ($group['success'] as $state) {
            $success[] = $state['card']['CardFullName'] . ' - verified';
        }
        */

        return $message;
    }

    private function getErrorMessage(int $number): string
    {
        switch ($number) {
            case 1:
                return self::ERROR_FAILED_LOAD_DIRECT_LINK;

            case 2:
            case 20:
                return self::ERROR_FOUND_APPLY_NOW_BUTTON;

            case 3:
                return self::ERROR_FAILED_LOAD_APPLY_NOW_LINK;

            case 10:
                return self::ERROR_FORM_NOT_FOUND;

            case 40:
            case 41:
                return self::ERROR_POSSIBLE_SPECIAL_CONDITIONS;

            case 50:
                return 'site error';
        }

        return 'Unknown Error';
    }

    private function getDriver(): \SeleniumDriver
    {
        $seleniumRequest = new \SeleniumFinderRequest(
            \SeleniumFinderRequest::BROWSER_CHROME,
            \SeleniumFinderRequest::CHROME_95
        );

        $seleniumOptions = new \SeleniumOptions();
        // $seleniumOptions->proxyHost = 'host.docker.internal';
        // $seleniumOptions->proxyPort = 3128;

        if (self::IS_USE_PROXY) {
            $this->http = new \HttpBrowser(null, $this->curlDriver);
            // $this->setProxyBrightData(true);

            //            $proxy = $this->proxyDOP(Settings::DATACENTERS_NORTH_AMERICA);
            //            preg_match('/:(\d+)$/ims', $proxy, $matches);
            //            $seleniumOptions->proxyHost = preg_replace('/:\d+$/ims', '', $proxy);
            //            $seleniumOptions->proxyPort = (int) $matches[1];
            $seleniumOptions->proxyHost = '104.160.36.49';
            $seleniumOptions->proxyPort = 3128;

            // $this->isRewardAvailability = false;
            // $this->setProxyGoProxies();
        }

        $request = FingerprintRequest::chrome();
        $request->browserVersionMin = \HttpBrowser::BROWSER_VERSION_MIN - 3;
        $request->platform = random_int(0, 1) ? 'MacIntel' : 'Win32';
        $fingerprint = $this->fingerprintFactory->getOne([$request]);
        $seleniumOptions->fingerprint = $fingerprint->getFingerprint();

        return $this->seleniumDriverFactory->getDriver(
            $seleniumRequest,
            $seleniumOptions,
            $this->logger
        );
    }

    private function waitElement(\WebDriverBy $by, $timeout = 15, $visible = true)
    {
        /** @var \RemoteWebElement $element */
        $element = null;
        $this->waitFor(
            function () use ($by, &$element, $visible) {
                $elements = $this->webDriver->findElements($by);

                foreach ($elements as $element) {
                    if ($visible && !$element->isDisplayed()) {
                        $element = null;
                    }

                    return null !== $element;
                }

                return false;
            },
            $timeout
        );

        return $element;
    }

    private function waitFor($whileCallback, $timeoutSeconds = 30): bool
    {
        $start = time();

        do {
            try {
                if (call_user_func($whileCallback)) {
                    return true;
                }
            } catch (\Exception $e) {
                // $this->reconnectFirefox($e);
            }
            sleep(1);
        } while ((time() - $start) < $timeoutSeconds);

        return false;
    }

    private function showCardsWithoutLinks(array $cards): void
    {
        $cardsWithoutClickUrl = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT CreditCardID, CardFullName, DirectClickURL
            FROM CreditCard
            WHERE CreditCardID NOT IN (?)
        ',
            [array_column($cards, 'CreditCardID')],
            [Connection::PARAM_INT_ARRAY]
        );

        $this->output->writeln(['', '', "Cards we don't check:"]);

        foreach ($cardsWithoutClickUrl as $card) {
            $this->output->writeln(
                str_pad($card['CreditCardID'], 4, ' ')
                . ': ' . $card['CardFullName']
                . ' [' . $card['DirectClickURL'] . ']'
            );
        }
    }

    private function checkSuccessCardClicks(): void
    {
        $successCards = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT CreditCardID, CardFullName, SuccessCheckDate, QsCreditCardID
            FROM CreditCard
            WHERE
                    SuccessCheckDate IS NOT NULL
                AND SuccessCheckDate >= DATE_SUB(NOW(), INTERVAL ' . self::DAYS_SUCCESS_CHECK_PAST . ' DAY) 
        ');

        $result = [
            'success' => [],
            'failure' => [],
            'process' => [],
        ];

        $now = new \DateTime();

        foreach ($successCards as $card) {
            $cardId = (int) $card['CreditCardID'];
            $qsCardId = (int) $card['QsCreditCardID'];

            $successCheckDate = new \DateTime($card['SuccessCheckDate']);
            $successCheckDate->sub(new \DateInterval('P2D'));
            $successCheckDate->setTime(0, 0);

            $qsTransaction = $this->entityManager->getConnection()->fetchAssociative("
                SELECT qt.QsTransactionID
                FROM QsTransaction qt
                WHERE
                        qt.QsCreditCardID = " . $qsCardId . "
                    AND qt.CID LIKE '" . self::VAR2_ARGS['cid'] . "'
                    AND qt.ClickTime > '" . $successCheckDate->format('Y-m-d H:i') . "'
                ORDER BY qt.ClickTime DESC
                LIMIT 1
            ");

            if (empty($qsTransaction)) {
                $diff = $now->diff(new \DateTime($card['SuccessCheckDate']));

                $card['lastQsTransaction'] = $this->entityManager->getConnection()->fetchOne("
                    SELECT qt.ClickTime
                    FROM QsTransaction qt
                    WHERE
                            qt.QsCreditCardID = " . $qsCardId . "
                        AND qt.CID LIKE '" . self::VAR2_ARGS['cid'] . "'
                    ORDER BY qt.ClickTime DESC
                    LIMIT 1
                ");

                if ($diff->days < self::DAYS_AFTER_DETECT_DATA) {
                    $result['process'][$cardId] = $card;
                } else {
                    $result['failure'][$cardId] = $card;
                }

                continue;
            }

            $result['success'][$cardId] = $card;
        }

        $processedCars = array_merge(
            array_keys($result['success']),
            array_keys($result['failure']),
            array_keys($result['process'])
        );
        $otherCards = $this->entityManager->getConnection()->fetchAllAssociative("
            SELECT CreditCardID, CardFullName, SuccessCheckDate
            FROM CreditCard
            WHERE
                    CreditCardID NOT IN (?)
                AND QsAffiliate IN (?)
                AND DirectClickURL IS NOT NULL
                AND DirectClickURL <> ''
        ",
            [
                $processedCars,
                [CreditCard::QS_AFFILIATE_DIRECT, CreditCard::QS_AFFILIATE_CARDRATINGS],
            ],
            [Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY]
        );

        $message = [];
        $message['blocks'] = [];
        $message['blocks'][] = ['type' => 'divider'];
        $message['blocks'][] = [
            'type' => 'header',
            'text' =>
                [
                    'type' => 'plain_text',
                    'text' => 'QS Transaction (result of successful checks for the last ' . self::DAYS_SUCCESS_CHECK_PAST . ' days)',
                ],
        ];

        $getList = static function (array $cards, bool $isLastShow = true): array {
            $list = [];

            foreach ($cards as $card) {
                $date = empty($card['SuccessCheckDate'])
                    ? 'never'
                    : date('m/d/Y H:i', strtotime($card['SuccessCheckDate']));

                $list[] = $date . ' - ' . $card['CardFullName']
                    . ($isLastShow && !empty($card['lastQsTransaction'])
                        ? ' (last ' . date('m/d/Y H:i', strtotime($card['lastQsTransaction'])) . ')'
                        : '');
            }

            return $list;
        };

        $success = $getList($result['success'], false);
        $failure = $getList($result['failure']);
        $process = $getList($result['process']);

        $message['blocks'][] = ['type' => 'divider'];
        $message['blocks'][] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '>*Success:* ' . (empty($success) ? '0' : ''),
            ],
        ];

        if (!empty($success)) {
            $message = $this->chunkMessage($success, $message);
        }

        $message['blocks'][] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '>*Process* (less than ' . self::DAYS_AFTER_DETECT_DATA . ' days have passed): '
                    . (empty($process) ? '0' : ''),
            ],
        ];

        if (!empty($process)) {
            $message = $this->chunkMessage($process, $message);
        }

        $message['blocks'][] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => '>*Failure:* ' . (empty($failure) ? '0' : ''),
            ],
        ];

        if (!empty($failure)) {
            $message = $this->chunkMessage($failure, $message);
        }

        if (!empty($otherCards)) {
            $message['blocks'][] = ['type' => 'divider'];
            $message['blocks'][] = [
                'type' => 'header',
                'text' =>
                    [
                        'type' => 'plain_text',
                        'text' => 'Other cards with "Qs Affiliate" (successful check date is more than ' . self::DAYS_AFTER_DETECT_DATA . ' days or unknown)',
                    ],
            ];

            $message = $this->chunkMessage($getList($otherCards), $message);
        }

        $this->appBot->send(Slack::CHANNEL_AW_STATS, $message);
    }

    private function chunkMessage($list, $message): array
    {
        $list = array_chunk($list, 5);

        foreach ($list as $items) {
            $message['blocks'][] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => implode("\n", $items),
                ],
            ];
        }

        return $message;
    }
}
