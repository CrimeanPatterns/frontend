<?php

namespace AwardWallet\MainBundle\Globals\Updater\Engine;

use AwardWallet\Common\Parsing\ParsingConstants;
use AwardWallet\Common\Parsing\Solver\MasterSolver;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Event\LoyaltyPrepareAccountRequestEvent;
use AwardWallet\MainBundle\Globals\Updater\Engine\CheckAccountResponse as CheckAccountResponseLegacyEngine;
use AwardWallet\MainBundle\Globals\Updater\UpdaterUtils;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\CheckAccountResponsePreparer;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\AccountProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\ItinerariesProcessor;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\Converters\PropertiesItinerariesConverter;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountRequest;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryColumn;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\AccountCheckReportConverter;
use AwardWallet\MainBundle\Updater\AccountProgress;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Local implements UpdaterEngineInterface
{
    /** @var LoggerInterface */
    private $logger;
    /** @var AccountProcessor */
    private $accountProcessor;
    /** @var AccountRepository */
    private $accountRepository;
    /** @var Connection */
    private $connection;
    /** @var ItinerariesProcessor */
    private $itinerariesProcessor;
    /** @var AccountCheckReportConverter */
    private $accountCheckResponseConverter;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var PropertiesItinerariesConverter
     */
    private $propertiesItinerariesConverter;
    /**
     * @var MasterSolver
     */
    private $solver;

    private CheckAccountResponsePreparer $responsePreparer;
    private AccountProgress $accountProgress;

    public function __construct(
        LoggerInterface $logger,
        AccountProcessor $accountProcessor,
        AccountRepository $accountRepository,
        Connection $connection,
        ItinerariesProcessor $itinerariesProcessor,
        AccountCheckReportConverter $accountCheckReportConverter,
        PropertiesItinerariesConverter $propertiesItinerariesConverter,
        ParsingConstants $parsingConstants,
        EventDispatcherInterface $eventDispatcher,
        MasterSolver $solver,
        CheckAccountResponsePreparer $responsePreparer,
        AccountProgress $accountProgress
    ) {
        $this->logger = $logger;
        $this->accountProcessor = $accountProcessor;
        $this->accountRepository = $accountRepository;
        $this->connection = $connection;
        $this->itinerariesProcessor = $itinerariesProcessor;
        $this->accountCheckResponseConverter = $accountCheckReportConverter;
        $this->eventDispatcher = $eventDispatcher;
        $this->propertiesItinerariesConverter = $propertiesItinerariesConverter;
        $this->solver = $solver;
        $this->responsePreparer = $responsePreparer;
        $this->accountProgress = $accountProgress;
    }

    /**
     * @return \AwardWallet\MainBundle\Globals\Updater\Engine\CheckAccountResponse[]
     */
    public function sendAccounts(array $accounts, $options = 0, $source = null): array
    {
        $accounts = $this->prepareAccounts($accounts, $options);
        $result = [];

        foreach ($accounts as $account) {
            $options = \CommonCheckAccountFactory::getDefaultOptions();
            $options->checkIts = $account['ParseItineraries'] ?? true;
            $options->checkHistory = true;
            $options->source = $source;
            [$report, $response] = $this->checkAccount($account['AccountID'], $options, $account['Code']);
            $result[$account['AccountID']] = new CheckAccountResponseLegacyEngine(
                $response->getRequestid(),
                $account['AccountID'],
                null,
                null
            );
        }

        return $result;
    }

    public function getUpdateSlots(Usr $usr)
    {
        return 1;
    }

    public function getLogs($partner, $accountId, $providerCode = null, $login = null, $login2 = null, $login3 = null)
    {
        $logs = glob(getSymfonyContainer()->getParameter("checker_logs_dir") . "/" . sprintf("%03d", round($accountId) / 1000) . "/account-{$accountId}-*");

        if (count($logs) > 0) {
            return $logs;
        }

        $logs = glob(getSymfonyContainer()->getParameter("checker_logs_dir") . "/account-{$accountId}-*");

        return $logs;
    }

    public function retrieveConfirmation(array $fields, Provider $provider, array &$trips, Usr $user, ?Useragent $familyMember = null)
    {
        if (null !== $familyMember && !$familyMember->isFamilyMember()) {
            throw new \InvalidArgumentException("Expected family member, got connection");
        }

        $providerCode = $provider->getCode();
        $checker = GetAccountChecker($providerCode);
        $checker->db = new \DatabaseHelper($this->connection);
        $error = $checker->CheckConfirmationNumber($fields, $checker->Itineraries, ['Code' => $providerCode]);
        $checker->ArchiveLogs($checker->http->LogDir, file_get_contents($checker->http->LogDir . "/log.html"), null, $checker->AccountFields);
        $checker->Cleanup();
        $checker->http->cleanup();

        if (!empty($error)) {
            return $error;
        }

        $result = $this->propertiesItinerariesConverter->extractItinerariesFromProperties($provider, ['Itineraries' => $checker->Itineraries]);
        $options = SavingOptions::savingByConfirmationNumber(new Owner($user, $familyMember), $provider->getCode(), $fields);
        $report = $this->itinerariesProcessor->save($result, $options);

        $tripsTouched = array_merge($report->getAdded(), $report->getUpdated());
        $tripsData = [];

        /** @var Itinerary $item */
        foreach ($tripsTouched as $item) {
            $tripsData[] = $item->getIdString();
        }
        $trips = $tripsData;

        return null;
    }

    public function getRedirectFrameUrl(?Account $account = null, Usr $user, ?Provider $provider = null)
    {
        $accountId = !empty($account) ? $account->getAccountid() : 0;

        if (empty($provider) && !empty($account) && !empty($account->getProviderid())) {
            $provider = $account->getProviderid();
        }

        if ($provider->getCode() == 'marriott') {
            return '/rewards/myAccount/default.mi?ID=' . $accountId;
        } else {
            return '/account/redirectFrame.php?ID=' . $accountId;
        }
    }

    public function changePassword(Account $account)
    {
    }

    public function getProviderInfo(string $code): ProviderInfoResponse
    {
        $canCheckHistory = (int) $this->connection->fetchColumn("select CanCheckHistory from Provider where Code = '{$code}'");

        return (new ProviderInfoResponse())->setHistorycolumns($this->getHistoryColumns($code))->setCanparsehistory($canCheckHistory === 1);
    }

    public function getCheckStrategy()
    {
        return \CommonCheckAccountFactory::STRATEGY_CHECK_LOCAL;
    }

    /**
     * we pass providerCode for provider groups checking functionality.
     *
     * @return array{0: bool|object, 1: CheckAccountResponse}
     */
    private function checkAccount(int $accountId, \AuditorOptions $options, string $providerCode): array
    {
        $saved = false;
        $response = null;

        if (is_null($options->source)) {
            $options->source = UpdaterEngineInterface::SOURCE_DESKTOP;
        }

        try {
            $checkStrategy = new \LocalCheckStrategy();
            /** @var Account $accountEntity */
            $accountEntity = $this->accountRepository->find($accountId);
            $request = new CheckAccountRequest();
            $request
                ->setProvider($providerCode)
                ->setPriority($options->priority)
                ->setLogin($accountEntity->getLogin())
                ->setLogin2($accountEntity->getLogin2())
                ->setLogin3($accountEntity->getLogin3())
                ->setUserdata(new UserData())
            ;
            $this->eventDispatcher->dispatch(new LoyaltyPrepareAccountRequestEvent($accountEntity, $request), LoyaltyPrepareAccountRequestEvent::NAME);
            $account = new \Account($accountId);
            $account->setAccountInfo(array_merge($account->getAccountInfo(), ["ProviderCode" => $providerCode]));
            $report = $checkStrategy->check($account, $options);
            $report->providerCode = $providerCode;
            $saveReport = new ProcessingReport();

            if ($report instanceof \AccountCheckReport) {
                $report->account = $account;
                $report->filter();
                $response = $this->accountCheckResponseConverter->convert(
                    $report,
                    $options->source,
                    $options->checkIts
                );

                if (empty($report->checker->Itineraries) && !empty($report->checker->itinerariesMaster->getItineraries())) {
                    $report->checker->itinerariesMaster->checkValid();
                    $extra = new \AwardWallet\Common\Parsing\Solver\Extra\Extra();
                    $extra->provider = \AwardWallet\Common\Parsing\Solver\Extra\ProviderData::fromArray([
                        'Code' => $accountEntity->getProviderid()->getCode(),
                        'ProviderID' => $accountEntity->getProviderid()->getProviderid(),
                        'IATACode' => $accountEntity->getProviderid()->getIATACode(),
                        'Kind' => $accountEntity->getProviderid()->getKind(),
                        'ShortName' => $accountEntity->getProviderid()->getShortname(),
                    ]);
                    $extra->context->partnerLogin = 'awardwallet';
                    $this->solver->solve($report->checker->itinerariesMaster, $extra);

                    $loader = new \AwardWallet\Common\API\Converter\V2\Loader();
                    $result = [];

                    foreach ($report->checker->itinerariesMaster->getItineraries() as $itinerary) {
                        $result[] = $loader->convert($itinerary, $extra);
                    }
                    $response->setItineraries($result);
                }

                \TAccountChecker::ArchiveLogs($report->checker->http->LogDir, file_get_contents($report->checker->http->LogDir . "/log.html"), "account-" . $report->account->getAccountId() . "-" . time(), $report->checker->AccountFields);
                $report->checker->Cleanup();
                /* старая логика сохранения (убираем сохранение истории) */
                $options->checkHistory = false;
                $options->checkIts = false;

                \CommonCheckAccountFactory::manuallySave($accountId, $report, $options);
                /** @var Account $account */
                $account = $this->accountRepository->find($accountId);
                /* новый код, на который нужно переехать и избавиться от $auditor->save() */
                $this->responsePreparer->prepare($account, $response);
                $saveReport = $this->accountProcessor->saveAccount($account, $response);
                $saved = $report;
            } else {
                $response = new CheckAccountResponse();
            }

            $response->setRequestid('local_' . \bin2hex(\random_bytes(16)));

            if (is_bool($report)) {
                $saved = $report;
            }

            $this->accountProgress->resetLoyaltyRequest($response->getRequestid());
            $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_LOCAL_CHECK));
            $this->eventDispatcher->dispatch(
                new AccountUpdatedEvent($account, $response, $saveReport, AccountUpdatedEvent::UPDATE_METHOD_LOYALTY),
                AccountUpdatedEvent::NAME
            );
        } catch (\AccountNotFoundException $e) {
            // ignore deleted accounts
        }

        return [$saved, $response];
    }

    private function prepareAccounts(array $accounts, $options)
    {
        foreach ($accounts as &$account) {
            if (self::OPTION_IT_AUTO & $options) {
                $account['AutoGatherPlans'] = UpdaterUtils::shouldCheckTrips($account);
            }

            if (!array_key_exists('Code', $account)) {
                $account['Code'] = $this->connection->fetchOne(
                    "select p.Code from Account a join Provider p on a.ProviderID = p.ProviderID where a.AccountID = ?",
                    [$account['AccountID']],
                );
            }
        }

        return $accounts;
    }

    private function getHistoryColumns($providerCode): array
    {
        $checker = $checker = GetAccountChecker($providerCode);
        $columns = $checker->GetHistoryColumns();

        $result = [];

        if (empty($columns)) {
            return $result;
        }

        $hiddenCols = $checker->GetHiddenHistoryColumns();

        foreach ($columns as $name => $kind) {
            $column = HistoryColumn::createFromTAccountCheckerDefinition($name, $kind);
            $column->setIsHidden(in_array($name, $hiddenCols));
            $result[] = $column;
        }

        return $result;
    }
}
