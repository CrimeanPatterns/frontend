<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Processors;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Repositories\ItineraryRepositoryInterface;
use AwardWallet\MainBundle\Entity\Repositories\TripRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Event\AccountBalanceChangedEvent;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\SavingOptions;
use AwardWallet\MainBundle\Loyalty\Resources\Answer;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\History;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\CapitalcardsHelper;
use AwardWallet\MainBundle\Timeline\Diff\Tracker;
use Clock\ClockNative;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\milliseconds;

class AccountProcessor
{
    private LoggerInterface $logger;

    private BalanceProcessor $balanceProcessor;

    private DetectedCardProcessor $detectedCardProcessor;

    private HistoryProcessor $historyProcessor;

    private SubAccountProcessor $subAccountProcessor;

    private ItinerariesProcessor $itinerariesProcessor;

    private Tracker $tracker;

    private EntityManagerInterface $entityManager;

    private EventDispatcherInterface $eventDispatcher;

    /**
     * @var ItineraryRepositoryInterface[]
     */
    private $repositories;

    public function __construct(
        LoggerInterface $logger,
        BalanceProcessor $balanceProcessor,
        HistoryProcessor $historyProcessor,
        SubAccountProcessor $subAccountProcessor,
        ItinerariesProcessor $itinerariesProcessor,
        DetectedCardProcessor $detectedCardProcessor,
        Tracker $tracker,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        iterable $repositories
    ) {
        $this->logger = $logger;
        $this->balanceProcessor = $balanceProcessor;
        $this->historyProcessor = $historyProcessor;
        $this->subAccountProcessor = $subAccountProcessor;
        $this->itinerariesProcessor = $itinerariesProcessor;
        $this->detectedCardProcessor = $detectedCardProcessor;
        $this->tracker = $tracker;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->repositories = $repositories;
    }

    public function saveAccount(Account $account, CheckAccountResponse $response): ProcessingReport
    {
        $this->logger->pushProcessor(function (array $record) use ($account) {
            $record['context']['AccountID'] = $account->getAccountid();
            $record['context']['UserID'] = $account->getUser()->getId();

            return $record;
        });

        try {
            $options = SavingOptions::savingByAccount($account, $this->isInitializedByUser($response));
            /* TODO: переделать после релиза v2 itineraries */
            $this->updateAuthInfo($account, $response->getInvalidanswers() ?? []);
            $result = $this->saveItineraries($account, $response, $options);

            if (in_array($response->getState(), [ACCOUNT_CHECKED, ACCOUNT_WARNING])) {
                $this->subAccountProcessor->process($account, $response);
                $this->saveBalance($account, $response);
                $this->detectedCardProcessor->process($account, $response);
            }
            $this->entityManager->flush();
            // history should be saved after itineraries, to link history to itineraries in PlanLinker
            $this->saveHistory($account, $response);
        } finally {
            $this->logger->popProcessor();
        }

        return $result;
    }

    /**
     * @return Itinerary[]
     */
    private function getItineraries(Account $account): array
    {
        $itineraries = [];

        foreach ($this->repositories as $repository) {
            $qb = $repository->createQueryBuilder('t');

            if ($repository instanceof TripRepository) {
                $qb->join('t.segments', 'tripSegments');
            }
            $criteria = $repository->getFutureCriteria();
            $criteria->andWhere(Criteria::expr()->eq('t.account', $account));
            $qb->addCriteria($criteria);
            $its = $qb->getQuery()->getResult();
            $itineraries = array_merge($itineraries, $its);
        }

        return $itineraries;
    }

    private function isInitializedByUser(CheckAccountResponse $response): bool
    {
        if (null === $response->getUserdata()) {
            return false;
        }
        $initializedByUser = false;

        if (ConfigValue(CONFIG_TRAVEL_PLANS)) {
            /** @var UserData $userData */
            $userData = $response->getUserdata();

            switch ($userData->getSource()) {
                case UpdaterEngineInterface::SOURCE_DESKTOP:
                case UpdaterEngineInterface::SOURCE_MOBILE:
                    $initializedByUser = true;

                    break;
            }
        }

        return $initializedByUser;
    }

    private function shouldSaveItineraries(CheckAccountResponse $response): bool
    {
        // Do we know for a fact that there are no future reservations?
        if ($response->getNoitineraries()) {
            $this->logger->info("NoItineraries is true");

            return true;
        }

        // Probability of a broken parser - do not check
        if (empty($response->getItineraries())) {
            $this->logger->info("no itineraries in response, but NoItineraries is false, broken parser?");

            return false;
        }

        // Everything seems to be in order - just check
        return true;
    }

    private function saveItineraries(Account $account, CheckAccountResponse $response, SavingOptions $options): ProcessingReport
    {
        $report = new ProcessingReport();

        $itineraries = $response->getItineraries();

        foreach ($this->getItineraries($account) as $accountItinerary) {
            // After an update itineraries with Parsed = TRUE are being reported as such to the user
            $accountItinerary->setParsed(false);
        }

        if ($response->getUserdata()->isCheckPastIts()) {
            $account->setLastCheckPastItsDate(new \DateTime());
        }
        $this->entityManager->flush();
        $checkedItineraries = $response->haveCheckedItineraries();

        if ($checkedItineraries) {
            if ($this->shouldSaveItineraries($response)) {
                $oldProperties = $this->tracker->getProperties($account->getId());
                $report = $this->itinerariesProcessor->save($itineraries, $options);

                if (count($report->getAdded()) > 0 || count($report->getUpdated()) > 0 || $response->getNoitineraries()) {
                    /** @var Itinerary[] $obsoleteItineraries */
                    $obsoleteItineraries = array_udiff(
                        $this->getItineraries($account),
                        $report->getAdded(),
                        $report->getUpdated(),
                        function (Itinerary $itineraryA, Itinerary $itineraryB) {
                            return $itineraryA->getId() <=> $itineraryB->getId();
                        }
                    );

                    foreach ($obsoleteItineraries as $obsoleteItinerary) {
                        if ($obsoleteItinerary->isUndeleted()) {
                            continue;
                        }
                        $this->logger->info("itinerary {$obsoleteItinerary->getKind()} {$obsoleteItinerary->getId()} is obsolete, hiding it");
                        $obsoleteItinerary->setHidden(true);
                    }
                }
                $this->tracker->recordChanges($oldProperties, $account->getId(), $account->getUser()->getUserid());
            }
        } else {
            $this->logger->info("not checked Itineraries");
        }

        if ($checkedItineraries && $response->getNoitineraries()) {
            $this->logger->info("set NoItineraries");
            $account->setItineraries(-1);
        } else {
            $itCount = count(array_filter($this->getItineraries($account), function (Itinerary $itinerary) { return !$itinerary->getHidden(); }));
            $this->logger->info("set Itineraries to {$itCount}");
            $account->setItineraries($itCount);
        }

        return $report;
    }

    private function saveHistory(Account $account, CheckAccountResponse $response): void
    {
        if ($response->getHistory() instanceof History) {
            // TODO: отрефакторить HistoryProcessor под интерфейс saveAccountHistory(History, SavingOptions)
            $connection = $this->entityManager->getConnection();
            $clock = new ClockNative();
            $maxRetries = 20;

            foreach (\range(1, $maxRetries) as $retry) {
                try {
                    $this->historyProcessor->saveAccountHistory($account->getAccountid(), $response->getHistory());
                    $deadlockException = null;

                    break;
                } catch (DeadlockException $deadlockException) {
                    if (!$this->entityManager->isOpen()) {
                        $this->logger->info('merchant deadlock: entity manager is closed! throw!');

                        throw $deadlockException;
                    }

                    if ($connection->isTransactionActive()) {
                        $connection->rollBack();
                    }

                    if ($retry < $maxRetries) {
                        $clock->sleep(milliseconds(50)->times(2 ** \min($retry - 1, 2))); // 50 ms, 100 ms, 200 ms, 200 ms, 200 ms ...
                    }
                }
            }

            if (isset($deadlockException)) {
                $this->logger->info('merchant deadlock: retries exceeded! throw!');

                throw $deadlockException;
            }

            $this->entityManager->flush();
            $this->logger->debug('Saving history done.');
        }
    }

    /**
     * this method will update AuthInfo, with fresh/expired tokens, used in capitalcards and other oauth-based providers.
     *
     * @param Answer[] $invalidAnswers
     */
    private function updateAuthInfo(Account $account, array $invalidAnswers): void
    {
        $authInfo = $account->getAuthInfo();

        if ($authInfo === null) {
            return;
        }

        foreach ($invalidAnswers as $answer) {
            if ($answer->getQuestion() === 'refresh_token' && $answer->getAnswer() === 'none') {
                $this->logger->info("got refresh_token = none, setting AuthInfo field to null");
                $account->setAuthInfo(null);
            }

            if (in_array($answer->getQuestion(), ['rewards', 'tx']) && $answer->getAnswer() === 'none') {
                $this->logger->info("got {$answer->getQuestion()} = none, setting AuthInfo {$answer->getQuestion()} field to null");
                $newAuthInfo = CapitalcardsHelper::decodeSavedAuthInfo($authInfo);
                $newAuthInfo[$answer->getQuestion()] = null;
                $account->setAuthInfo(CapitalcardsHelper::encodeAuthInfo($newAuthInfo));
            }
        }
    }

    private function saveBalance(Account $account, CheckAccountResponse $response)
    {
        $fireEvent = $this->balanceProcessor->saveAccountBalance($account, $response->getBalance());
        $changedSubAccounts = [];

        if (is_array($subAccounts = $response->getSubaccounts()) && count($subAccounts) > 0) {
            foreach ($subAccounts as $subAccount) {
                $code = $subAccount->getCode();

                if (is_null($code)) {
                    continue;
                }

                $subAccountEntity = it($account->getSubAccountsEntities())
                    ->filter(function (Subaccount $sub) use ($code) {
                        return $sub->getCode() === $code;
                    })
                    ->first();

                if ($subAccountEntity) {
                    $allowFloat = $account->getProviderid() ? $account->getProviderid()->getAllowfloat() : true;

                    if ($this->balanceProcessor->saveSubAccountBalance($subAccountEntity, filterBalance($subAccount->getBalance(), $allowFloat))) {
                        $changedSubAccounts[] = $subAccountEntity;
                    }
                }
            }
        }

        if ($fireEvent || count($changedSubAccounts) > 0) {
            $this->eventDispatcher->dispatch(
                new AccountBalanceChangedEvent(
                    $account,
                    $changedSubAccounts,
                    $response->getUserdata()->getSource(),
                    $fireEvent
                )
            );
        }
    }
}
