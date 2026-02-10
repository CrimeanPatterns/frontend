<?php

namespace AwardWallet\MainBundle\Loyalty;

use AwardWallet\Common\OneTimeCode\ProviderQuestionAnalyzer;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Event\LoyaltyPrepareAccountRequestEvent;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Saver;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\HistoryState\HistoryStateBuilder;
use AwardWallet\MainBundle\Loyalty\Resources\Answer;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountCallback;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckConfirmationResponse;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportPackageRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckExtensionSupportRequest;
use AwardWallet\MainBundle\Loyalty\Resources\Coupon;
use AwardWallet\MainBundle\Loyalty\Resources\DetectedCard;
use AwardWallet\MainBundle\Loyalty\Resources\History;
use AwardWallet\MainBundle\Loyalty\Resources\InputField;
use AwardWallet\MainBundle\Loyalty\Resources\Location;
use AwardWallet\MainBundle\Loyalty\Resources\Picture;
use AwardWallet\MainBundle\Loyalty\Resources\Property;
use AwardWallet\MainBundle\Loyalty\Resources\RequestItemHistory;
use AwardWallet\MainBundle\Loyalty\Resources\SubAccount;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\EmailParsing\Client\Api\EmailScannerApi;
use AwardWallet\MainBundle\Timeline\Diff\ItineraryTracker;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class Converter
{
    public const SERIALIZE_FORMAT = 'json';

    public const USER_CHECK_REQUEST_PRIORITY = 7;
    public const BACKGROUND_CHECK_REQUEST_PRIORITY_MIN = 2;
    public const BACKGROUND_CHECK_REQUEST_PRIORITY_MEDIUM = 3;

    public const REQUEST_TIMEOUT = 300;

    private const ACCOUNT_LOCK_PREFIX = 'lty_sve_acc_lck_';
    private const ACCOUNT_RETRY_PREFIX = 'lty_sve_acc_rtr_';

    private LoggerInterface $logger;

    private SerializerInterface $serializer;

    private EntityManagerInterface $em;

    private UsrRepository $userRepository;

    private UseragentRepository $userAgentRepository;

    private AccountRepository $accountRepository;

    private ProviderRepository $providerRepository;

    private AccountProcessor $savingProcessor;

    private ItinerariesProcessor $itinerariesProcessor;

    private ItineraryTracker $tracker;

    private EmailScannerApi $scannerApi;

    private EventDispatcherInterface $eventDispatcher;

    private string $callbackUrlAccount;

    private string $callbackUrlConfirmation;

    private string $localPasswordsKey;

    private HistoryStateBuilder $historyStateBuilder;

    private Saver $saver;
    private \Memcached $memcached;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        EntityManagerInterface $em,
        UsrRepository $userRepository,
        UseragentRepository $userAgentRepository,
        AccountRepository $accountRepository,
        ProviderRepository $providerRepository,
        AccountProcessor $savingProcessor,
        ItinerariesProcessor $itinerariesProcessor,
        ItineraryTracker $tracker,
        EmailScannerApi $scannerApi,
        EventDispatcherInterface $eventDispatcher,
        $callbackUrlAccount,
        $callbackUrlConfirmation,
        $localPasswordsKey,
        HistoryStateBuilder $historyStateBuilder,
        Saver $saver,
        \Memcached $memcached
    ) {
        $this->logger = new ContextAwareLoggerWrapper($logger);
        $this->logger->pushContext(["worker" => "LoyaltyConverter"]);
        $this->serializer = $serializer;
        $this->em = $em;
        $this->savingProcessor = $savingProcessor;
        $this->itinerariesProcessor = $itinerariesProcessor;
        $this->tracker = $tracker;
        $this->eventDispatcher = $eventDispatcher;
        $this->callbackUrlAccount = $callbackUrlAccount;
        $this->callbackUrlConfirmation = $callbackUrlConfirmation;
        $this->localPasswordsKey = $localPasswordsKey;
        $this->userRepository = $userRepository;
        $this->userAgentRepository = $userAgentRepository;
        $this->accountRepository = $accountRepository;
        $this->providerRepository = $providerRepository;
        $this->scannerApi = $scannerApi;
        $this->historyStateBuilder = $historyStateBuilder;
        $this->saver = $saver;
        $this->memcached = $memcached;
    }

    public static function isBackgroundCheck(int $priority): bool
    {
        return in_array($priority, [self::BACKGROUND_CHECK_REQUEST_PRIORITY_MIN, self::BACKGROUND_CHECK_REQUEST_PRIORITY_MEDIUM]);
    }

    public function prepareCheckConfirmationRequest($provider, $fieldsArray, $userID, $userAgentId = null)
    {
        $fields = [];

        foreach ($fieldsArray as $code => $value) {
            $fields[] = (new InputField())->setCode($code)->setValue($value);
        }

        $request = new CheckConfirmationRequest();
        $request->setFields($fields)
                ->setProvider($provider)
                ->setPriority(self::USER_CHECK_REQUEST_PRIORITY)
                ->setTimeout(self::REQUEST_TIMEOUT)
                ->setBrowserExtensionAllowed($fieldsArray['browserExtensionAllowed'] ?? false)
                ->setCallbackUrl($this->callbackUrlConfirmation . '/' . self::USER_CHECK_REQUEST_PRIORITY)
                ->setUserId($userID)
                ->setUserData($userAgentId);

        return $request;
    }

    public function processCheckConfirmationResponse(CheckConfirmationResponse $response, CheckConfirmationRequest $request)
    {
        if (!empty($response->getMessage())) {
            return $response->getMessage();
        }

        /** @var Provider $provider */
        $provider = $this->providerRepository->findOneBy(['code' => $request->getProvider()]);

        if (null === $provider) {
            return 'Server error';
        }

        /** @var Usr $user */
        $user = $this->userRepository->find($request->getUserId());

        if (null !== $response->getUserdata()) {
            $userAgent = $this->userAgentRepository->find($response->getUserdata());
        } else {
            $userAgent = null;
        }
        $owner = OwnerRepository::getByUserAndUseragent($user, $userAgent);
        $confFieldsArray = [];

        /** @var InputField $field */
        foreach ($request->getFields() as $field) {
            $confFieldsArray[$field->getCode()] = $field->getValue();
        }
        $options = SavingOptions::savingByConfirmationNumber($owner, $provider->getCode(), $confFieldsArray);
        $report = $this->itinerariesProcessor->save($response->getItineraries(), $options);

        return $report;
    }

    /**
     * @param int $priority
     * @return CheckAccountRequest
     * @throws \Doctrine\ORM\ORMException
     */
    public function prepareCheckAccountRequest(Account $account, ?ConverterOptions $options = null, $priority = self::USER_CHECK_REQUEST_PRIORITY)
    {
        if (!isset($options)) {
            $options = new ConverterOptions();
        }

        $request = new CheckAccountRequest();
        $request->setProvider($account->getProviderid()->getCode())
                ->setLogin($account->getLogin())
                ->setPassword($account->getPass())
                ->setLogin2($account->getLogin2())
                ->setLogin3($account->getLogin3())
                ->setPriority($priority)
                ->setBrowserstate($this->accountRepository->loadBrowserState($account))
                ->setCallbackurl($this->callbackUrlAccount . '/' . $priority)
                ->setUserid($account->getUser()->getId())
                ->setLoginId($account->getLoginId())
        ;

        // Timeout
        if ($priority === self::USER_CHECK_REQUEST_PRIORITY && $options->getSource() !== UpdaterEngineInterface::SOURCE_OPERATIONS) {
            $request->setTimeout(self::REQUEST_TIMEOUT);
        }

        // Itineraries
        $parseIt = !empty($options->getParseItineraries()) ? $options->getParseItineraries() : $account->wantAutoCheckItineraries();
        $request->setParseitineraries($parseIt);
        $request->setParsePastItineraries(
            $parseIt && (
                is_null($account->getLastCheckPastItsDate())
                || $account->getLastCheckPastItsDate() < new \DateTime('-3 MONTH')
            )
        );

        // UserData
        $userData = (new UserData())->setAccountId($account->getAccountid())
            ->setPriority($priority)
            ->setSource($options->getSource())
            ->setCheckIts($parseIt)
            ->setCheckPastIts($request->isParsePastItineraries());

        if ($account->getProviderid()->getCode() && ProviderQuestionAnalyzer::getHoldsSession($account->getProviderid()->getCode())) {
            $userData->setOtcWait($account->getUser()->getValidMailboxesCount() > 0);
        }
        $request->setUserdata($userData);

        // Answers
        if (count($account->getAnswers()) > 0) {
            $answers = [];

            /** @var \AwardWallet\MainBundle\Entity\Answer $answerEntity */
            foreach ($account->getAnswers() as $answerEntity) {
                if (!$answerEntity->getValid() || empty($answerEntity->getQuestion()) || empty($answerEntity->getAnswer())) {
                    continue;
                }
                $answers[] = new Answer($answerEntity->getQuestion(), $answerEntity->getAnswer());
            }

            $request->setAnswers($answers);
        }

        // Capital One
        $authInfo = $account->getAuthInfo();

        if (!empty($authInfo)) {
            if (substr($authInfo, 0, 3) === 'v1:') {
                // capitalcards new format "v1:{"rewards":"encodedauthinfo","tx":"encodedauthinfo"}
                $tokenInfo = json_decode(substr($authInfo, 3), true);
                $tokenInfo = array_filter($tokenInfo, function ($value) { return !empty($value); });
                $tokenInfo = array_map(function (string $encoded) { return AESDecode(base64_decode($encoded), $this->localPasswordsKey); }, $tokenInfo);
            } else {
                // old format. only rewards. "encodedauthinfo"
                $tokenInfo = json_decode(AESDecode(base64_decode($account->getAuthInfo()), $this->localPasswordsKey));
            }

            $answers = [];

            if (!empty($tokenInfo)) {
                // pass access tokens as Answers

                foreach ($tokenInfo as $key => $value) {
                    $answers[] = new Answer($key, strval($value));
                }
            } else {
                $this->logger->warning("resetting badly serialized authinfo", ["AccountID" => $account->getAccountid(), "AuthInfo" => $account->getAuthInfo()]);
                $account->setAuthInfo(null);
                $this->em->persist($account);
                $this->em->flush($account);
            }

            // set access_token for both success and fail deserialization path, in fail path we will receive explicit auth error from loyalty
            $request->setLogin('oauth')
                    ->setLogin2($account->getAccountid())
                    ->setPassword('access_token')
                    ->setAnswers($answers);
        }

        // History
        $parseHistory = true; // always parse history

        /* ----- OLD
            $parseHistory = !empty($options->getParseHistory()) ? $options->getParseHistory() : $account->wantAutoCheckHistory();
        */
        if (true === $parseHistory) {
            // will decrypt loyalty history state, and modify it, to modify startDate if new data was parsed by browser extension
            $state = $this->historyStateBuilder->buildHistoryState(
                $account->getHistoryState(),
                $account->getProviderid()->getCacheversion(),
                $account->getAccountid()
            );
            $range = (empty($account->getHistoryState()) || $state === null) ? History::HISTORY_COMPLETE : History::HISTORY_INCREMENTAL2;
            $history = new RequestItemHistory();
            $history
                ->setRange($range)
                ->setState($state);

            $request->setHistory($history);
        }

        if ($options->isBrowserExtensionAllowed()) {
            $request->setBrowserExtensionAllowed(true);
        }

        // mailboxes
        if ($account->getUser()->getValidMailboxesCount() > 0) {
            $request->setMailboxConnected(true);
        }

        $this->eventDispatcher->dispatch(new LoyaltyPrepareAccountRequestEvent($account, $request), LoyaltyPrepareAccountRequestEvent::NAME);

        return $request;
    }

    /**
     * @param Account[] $accounts
     */
    public function prepareExtensionCheckSupportPackageRequest(array $accounts, bool $isMobile, bool $includeReadyProviders): CheckExtensionSupportPackageRequest
    {
        return (new CheckExtensionSupportPackageRequest())
            ->setPackage(
                it($accounts)
                ->map(fn (Account $account) =>
                    (new CheckExtensionSupportRequest())
                    ->setId($account->getId())
                    ->setProvider($account->getProviderid()->getCode())
                    ->setLogin($account->getLogin())
                    ->setLogin2($account->getLogin2())
                    ->setLogin3($account->getLogin3())
                    ->setIsMobile($isMobile)
                    ->setIncludeReadyProviders($includeReadyProviders)
                )
                ->toArray()
            );
    }

    /**
     * @return CheckAccountResponse[] unprocessed account callbacks
     */
    public function processCallbackPackage(CheckAccountCallback $callback): array
    {
        if (empty($callback->getResponse())) {
            return [];
        }

        $failures = [];

        /** @var CheckAccountResponse $responseItem */
        foreach ($callback->getResponse() as $responseItem) {
            if (!$responseItem instanceof CheckAccountResponse) {
                continue;
            }

            /** @var UserData $userData */
            $userData = $responseItem->getUserdata();
            /** @var Account $account */
            $account = $this->accountRepository->find($userData->getAccountid());

            if ($account === null) {
                $this->logger->warning("account not found", ["AccountID" => $userData->getAccountid()]);

                continue;
            }

            $this->memcached->increment(self::ACCOUNT_RETRY_PREFIX . $responseItem->getRequestid(), 1, 1, 180);

            if ($this->memcached->get(self::ACCOUNT_RETRY_PREFIX . $responseItem->getRequestid()) > 15) {
                $this->logger->error("account retried too long", ["AccountID" => $account->getId(), "RequestID" => $responseItem->getRequestid()]);

                continue;
            }

            if (!$this->memcached->add(self::ACCOUNT_LOCK_PREFIX . $account->getId(), true, 90)) {
                $this->logger->warning("failed to add account lock", ["AccountID" => $account->getId(), "RequestID" => $responseItem->getRequestid()]);
                $failures[] = $responseItem;

                continue;
            }

            try {
                $this->logger->pushProcessor(function (array $record) use ($responseItem, $userData, $account) {
                    $record['extra']['requestId'] = $responseItem->getRequestid();
                    $record['context']['accountId'] = $userData->getAccountId();

                    if ($account !== null) {
                        $record['context']['userId'] = $account->getUser()->getUserid();
                    }

                    return $record;
                });

                try {
                    $this->logger->info("saving account " . $userData->getAccountId());
                    $this->processCheckAccountResponse($responseItem, $account);
                } finally {
                    $this->logger->popProcessor();
                }
            } finally {
                $this->memcached->delete(self::ACCOUNT_LOCK_PREFIX . $account->getId());
            }
        }

        return $failures;
    }

    /* IMPORT AND REFACTOR FROM /web/api/awardwallet/CallbackService.php */
    public function retrieveSubAccounts(CheckAccountResponse $request)
    {
        $propertyField = [];

        if (empty($request->getSubaccounts())) {
            return $propertyField;
        }

        /** @var SubAccount $subAccount */
        foreach ($request->getSubaccounts() as $i => $subAccount) {
            if ($subAccount->getBalance() !== null) {
                $subAccount->setBalance(floatval($subAccount->getBalance()));
            }

            $propertyField[$i]['Code'] = $subAccount->getCode();
            $propertyField[$i]['DisplayName'] = $subAccount->getDisplayname();
            $propertyField[$i]['Balance'] = $subAccount->getBalance();

            // Subaccount Properties
            $subPropertyField = &$propertyField[$i];

            if (!empty($subAccount->getExpirationDate())) {
                $subPropertyField['ExpirationDate'] = $subAccount->getExpirationDate()->getTimestamp();
            }

            if (!empty($subAccount->getProperties())) {
                /** @var Property $property */
                foreach ($subAccount->getProperties() as $property) {
                    $subPropertyField[$property->getCode()] = $property->getValue();
                }
            }

            if (!empty($subAccount->getCoupons())) {
                $subPropertyField['Kind'] = 'C';
                $subPropertyField['Certificates'] = [];
                $certificates = &$subPropertyField['Certificates'];

                /** @var Coupon $coupon */
                foreach ($subAccount->getCoupons() as $coupon) {
                    $cid = $coupon->getId();
                    $certificates[$cid]['Id'] = $cid;
                    $certificates[$cid]['ExpiresAt'] = $coupon->getExpiresat();
                    $certificates[$cid]['File'] = $coupon->getFile();
                    $certificates[$cid]['Caption'] = $coupon->getCaption();
                    $certificates[$cid]['Used'] = $coupon->getUsed();
                    $certificates[$cid]['PurchasedAt'] = $coupon->getPurchasedat();
                    $certificates[$cid]['Status'] = $coupon->getStatus();
                }
            }

            if (!empty($subAccount->getLocations())) {
                $subPropertyField['Locations'] = [];
                $locs = &$subPropertyField['Locations'];

                /** @var Location $location */
                foreach ($subAccount->getLocations() as $location) {
                    $locs[]['Url'] = $location->getUrl();
                }
            }

            if (!empty($subAccount->getPictures())) {
                $subPropertyField['Picture'] = [];
                $pics = &$subPropertyField['Picture'];

                /** @var Picture $picture */
                foreach ($subAccount->getPictures() as $picture) {
                    $pics[]['Url'] = $picture->getUrl();
                }
            }

            if ($subAccount->getNeverexpires()) {
                $subPropertyField['ExpirationDate'] = (isset($subPropertyField['Kind']) && $subPropertyField['Kind'] == 'C') ? DATE_NEVER_EXPIRES : false;
            }
        }

        return $propertyField;
    }

    /* IMPORT AND REFACTOR FROM /web/api/awardwallet/CallbackService.php */
    public function retrieveProperties(CheckAccountResponse $request)
    {
        $propertyField = [];
        $properties = empty($request->getProperties()) ? [] : $request->getProperties();

        if ($request->getNeverexpires()) {
            $propertyField['AccountExpirationDate'] = false;
        }

        if (!empty($request->getExpirationDate()) && !$request->getNeverexpires()) {
            $propertyField['AccountExpirationDate'] = $request->getExpirationDate()->getTimestamp();
        }

        /** @var Property $property */
        foreach ($properties as $property) {
            if ($property->getValue() == 'false') {
                $property->setValue(false);
            }

            if ($property->getValue() == 'true') {
                $property->setValue(true);
            }

            $propertyField[$property->getCode()] = $property->getValue();
        }

        return $propertyField;
    }

    public function serialize($data)
    {
        return $this->serializer->serialize($data, self::SERIALIZE_FORMAT);
    }

    public function deserialize($data, $type = 'array')
    {
        return $this->serializer->deserialize($data, $type, self::SERIALIZE_FORMAT);
    }

    protected function processCheckAccountResponse(CheckAccountResponse $response, Account $account): void
    {
        if ($response->getState() === ACCOUNT_TIMEOUT) {
            $this->logger->warning("received timeout, will not save: " . $response->getDebuginfo(),
                ["userData" => $response->getUserdata()]);

            $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_LOYALTY_CHECK));
            $this->eventDispatcher->dispatch(new AccountUpdatedEvent($account, $response, new ProcessingReport(), AccountUpdatedEvent::UPDATE_METHOD_LOYALTY), AccountUpdatedEvent::NAME);

            return;
        }

        /** @var UserData $userData */
        $userData = $response->getUserdata();

        $checkResult = new \AccountCheckReport();
        $checkResult->source = $userData->getSource();
        $checkResult->account = $account;

        // START MAPPING
        if (trim($response->getBalance()) == '') {
            $response->setBalance(null);
        }
        $checkResult->balance = $response->getBalance();
        $checkResult->errorCode = $response->getState();

        if ($response->getMessage() == "" && $response->getQuestion() != "") {
            $checkResult->errorMessage = $response->getQuestion();
        } else {
            $checkResult->errorMessage = $response->getMessage();
        }
        /* TODO : Нужно ли? */
        $checkResult->errorReason = '';

        $checkResult->debugInfo = $response->getDebuginfo();
        $checkResult->question = $response->getQuestion();
        $checkResult->browserState = $response->getBrowserstate();

        if ('' !== $response->getOptions() && null !== $response->getOptions()) {
            $checkResult->options = explode(',', $response->getOptions());
        }
        $checkResult->providerCode = $response->getProvider();

        // Properties
        $checkResult->properties = [];
        $propertyField = &$checkResult->properties;
        $propertyField = array_merge($propertyField, $this->retrieveProperties($response));

        // SubAccounts
        $subAccounts = $this->retrieveSubAccounts($response);

        if (!empty($subAccounts)) {
            $propertyField['SubAccounts'] = $subAccounts;
        }

        // DetectedCards
        $detectedCards = $response->getDetectedcards();

        if (!empty($detectedCards)) {
            $cardsSimple = [];

            /** @var DetectedCard $card */
            foreach ($detectedCards as $card) {
                $cardsSimple[] = [
                    'Code' => $card->getCode(),
                    'DisplayName' => $card->getDisplayname(),
                    'CardDescription' => $card->getCarddescription(),
                ];
            }

            $propertyField['DetectedCards'] = serialize($cardsSimple);
        }

        // Invalid answers
        $checkResult->invalidAnswers = $this->retrieveInvalidAnswers($response);

        // Duration
        if (!empty($response->getRequestdate()) && !empty($response->getCheckdate())) {
            $startTime = $response->getRequestdate()->getTimestamp();
            $endTime = $response->getCheckdate()->getTimestamp();

            if ($startTime !== false && $endTime !== false) {
                $checkResult->duration = (float) ($endTime - $startTime);
            }
        }

        // END MAPPING
        $options = \CommonCheckAccountFactory::getDefaultOptions();
        $options->checkHistory = null !== $response->getHistory();
        $options->checkIts = false;
        $options->checkedBy = $this->getCheckedBy($userData, $response->getCheckedByClientBrowser() === true);
        $options->priority = $userData->getPriority();
        $options->source = $userData->getSource();

        try {
            // save logic above will not update entity, and listeners of AccountUpdatedEvent will receive outdated data
            // новая логика сохранения историй без AccountAuditor (пока только History)
            $report = $this->saver->save($account, $checkResult, $options, AccountUpdatedEvent::UPDATE_METHOD_LOYALTY, $response);
        } catch (\AccountException $e) {
            $this->logger->notice(get_class($e) . ":" . $e->getMessage() . ' - after loyalty check', ['accountId' => $account->getAccountid()]);
        }
    }

    /* IMPORT AND REFACTOR FROM /web/api/awardwallet/CallbackService.php */
    protected function retrieveInvalidAnswers(CheckAccountResponse $request)
    {
        $result = [];

        if (empty($request->getInvalidanswers())) {
            return $result;
        }

        /** @var Answer $answer */
        foreach ($request->getInvalidanswers() as $answer) {
            $result[$answer->getQuestion()] = $answer->getAnswer();
        }

        return $result;
    }

    private function getCheckedBy(UserData $userData, bool $checkedByClientBrowser): int
    {
        if (self::isBackgroundCheck($userData->getPriority())) {
            return Account::CHECKED_BY_BACKGROUND_CHECK;
        }

        if ($checkedByClientBrowser) {
            return Account::CHECKED_BY_USER_BROWSER_EXTENSION;
        }

        return Account::CHECKED_BY_USER_SERVER;
    }
}
