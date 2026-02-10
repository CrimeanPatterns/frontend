<?php

namespace AwardWallet\MainBundle\Updater\Plugin;

use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Service\OneTimeCodeProcessor\WaitTracker;
use AwardWallet\MainBundle\Updater\AccountState;
use AwardWallet\MainBundle\Updater\Event\FailEvent;
use AwardWallet\MainBundle\Updater\TickScheduler;
use AwardWallet\MainBundle\Updater\TimeoutResolver;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterSessionTickTask;
use Clock\ClockInterface;
use Doctrine\ORM\EntityManagerInterface;
use Duration\Duration;
use Psr\Log\LoggerInterface;

use function Duration\seconds;

class WaitEmailOTCPlugin extends AbstractPlugin
{
    use PluginIdentity;

    public const ID = 'waitingEmailOtc';
    private const STATE_TTL_SECONDS = 60;
    private const WAIT_START_MARK_KEY = 'otc_waiting_start_mark';
    private const WAIT_TIMEOUT_SECONDS = 30;

    private EntityManagerInterface $entityManager;
    private TickScheduler $tickScheduler;
    private ClockInterface $clock;
    private WaitTracker $waitTracker;
    private LoggerInterface $logger;
    private BinaryLoggerFactory $check;
    private TimeoutResolver $timeoutResolver;
    private static Duration $WAIT_TIMEOUT;

    public function __construct(
        EntityManagerInterface $entityManager,
        TickScheduler $tickScheduler,
        ClockInterface $clock,
        WaitTracker $waitTracker,
        LoggerInterface $logger,
        TimeoutResolver $timeoutResolver
    ) {
        $this->entityManager = $entityManager;
        $this->tickScheduler = $tickScheduler;
        $this->clock = $clock;
        $this->waitTracker = $waitTracker;
        $this->logger = (new ContextAwareLoggerWrapper($logger))
            ->withClass(self::class)
            ->withTypedContext();
        $this->check = (new BinaryLoggerFactory($this->logger))->toInfo()->uppercaseInfix();
        $this->timeoutResolver = $timeoutResolver;
        self::$WAIT_TIMEOUT = seconds(self::WAIT_TIMEOUT_SECONDS);
    }

    /**
     * @param AccountState[] $accountStates
     */
    public function tick(MasterInterface $master, $accountStates): void
    {
        $this->logger->pushContext(['updater_session_key' => $master->getKey()]);

        try {
            $needWaitTimeoutTask = false;

            foreach ($accountStates as $state) {
                $this->logger->pushContext(['account_id' => $state->account->getId()]);

                try {
                    $needWaitTimeoutTask |= $this->tickAccount($state, $master);
                } finally {
                    $this->logger->popContext();
                }
            }

            if ($needWaitTimeoutTask) {
                $this->tickScheduler->scheduleTick(
                    new UpdaterSessionTickTask($master->getKey()),
                    self::$WAIT_TIMEOUT
                );
            }
        } finally {
            $this->logger->popContext();
        }
    }

    private function tickAccount(AccountState $state, MasterInterface $master): bool
    {
        $account = $state->account;
        $check = $this->check;

        if ($this->isClientCheck($state)) {
            $state->popPlugin();

            return false;
        }

        $this->entityManager->refresh($account);
        /** @var ?Duration $waitStartMark */
        $waitStartMark = $state->getContextValue(self::WAIT_START_MARK_KEY);

        if (
            !$waitStartMark // do not perform flaky ->isWaitingOtc if we already started waiting
            && $check('account')->isNot('waiting OTC-code from email')
                ->on(!$this->waitTracker->isWaitingOtc($account, self::STATE_TTL_SECONDS))
        ) {
            $state->popPlugin();

            return false;
        }

        $loyaltyRequestId = $state->getSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY);

        if (null === $loyaltyRequestId) {
            // migration time only
            $this->logger->info("Aborting check, no loyalty request id");
            $master->addEvent(new FailEvent($state->account->getAccountid(), 'updater2.messages.fail.updater'));
            $master->removeAccount($state->account);

            return false;
        }

        $otcStartCheckTime = $this->waitTracker->getLastOtcCheckDate($account);
        $otcStartCheckTime = $otcStartCheckTime ? seconds($otcStartCheckTime) : null;

        if (
            $check('OTC start check mark')->does('exist')
                ->on($otcStartCheckTime)
        ) {
            $firstServerCheckStartTime = $state->getSharedValue(ServerCheckPlugin::SHARED_START_TIME_KEY);

            if (
                $check('OTC start check mark')->is('older than first server check start time')
                    ->on($otcStartCheckTime->lessThan($firstServerCheckStartTime))
            ) {
                // OTC check was sent before account started updating in this session.
                // Do not abuse user's account credentials.
                $state->popPlugin();
            } else {
                // act like we already sent account to second otc-check
                $this->waitForSecondCheck($state, $otcStartCheckTime, $master);
            }
        } else {
            if ($waitStartMark) {
                if (
                    $check('waiting for OTC start check mark')->is('timed out')
                        ->on($this->clock->isTimedOut($waitStartMark, self::$WAIT_TIMEOUT))
                ) {
                    $state->popPlugin();
                    $this->waitTracker->dontWaitOtc($account);
                }
            } else {
                $this->logger->info("waiting for OTC start check mark for " . self::$WAIT_TIMEOUT . ' to appear');
                $state->setContextValue(self::WAIT_START_MARK_KEY, $this->clock->current());

                return true;
            }
        }

        return false;
    }

    private function waitForSecondCheck(AccountState $state, Duration $secondCheckStartTime, MasterInterface $master): void
    {
        $this->logger->info('now falling back to second automatic check with OTC');
        /*
        $oldRequestId = $state->getSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY);
        $nextRequestId = $this->waitTracker->getNextRequestId($oldRequestId);

        if (null !== $nextRequestId) {
            $state->setSharedValue(ServerCheckPlugin::LOYALTY_REQUEST_ID_CONTEXT_KEY, $nextRequestId);
        } else {
            $this->logger->info('no next request id found, skipping second check');
            $state->popPlugin();

            return;
        }
        */

        $state->popPlugin();
        $state->pushPlugin(ServerCheckPlugin::ID, [
            'startTime' => $secondCheckStartTime,
        ]);
        $master->log($state->account, 'Second OTC check');
        $this->tickScheduler->scheduleTick(
            new UpdaterSessionTickTask($master->getKey()),
            $this->timeoutResolver->resolveForProvider($state->account->getProviderid())
        );
    }

    private function isClientCheck(AccountState $state): bool
    {
        $account = $state->account;
        $provider = $account->getProviderid();

        return
            (
                in_array($provider->getCheckinbrowser(), [CHECK_IN_CLIENT, CHECK_IN_MIXED])
                && $provider->getState() == PROVIDER_CHECKING_EXTENSION_ONLY
            )
            || !$provider->getCancheck()
            || $state->getSharedValue('checkedByExtension');
    }
}
