<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Updater\Plugin\GetProviderFromStateTrait;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterSessionTickTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\UpdaterSessionTimeoutTickTask;
use Duration\Duration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function Duration\seconds;

class TickScheduler
{
    use GetProviderFromStateTrait;
    private Duration $NEXT_TICK_SCHEDULER_COALESCING_INTERVAL;
    private Process $asyncProcess;
    private RequestSerializer $requestSerializer;
    private LoggerInterface $logger;

    public function __construct(
        ProviderRepository $providerRep,
        Process $process,
        RequestSerializer $requestSerializer,
        LoggerInterface $logger
    ) {
        $this->NEXT_TICK_SCHEDULER_COALESCING_INTERVAL = seconds(5);
        $this->asyncProcess = $process;
        $this->providerRepository = $providerRep;
        $this->requestSerializer = $requestSerializer;
        $this->logger =
            (new ContextAwareLoggerWrapper($logger))
            ->setMessagePrefix('updater tick scheduler: ')
            ->pushContext([Context::SERVER_MODULE_KEY => 'updater_tick_scheduler'])
            ->withTypedContext();
    }

    /**
     * Coalesces ticks by interval.
     *
     * @param AccountState[] $accountStates
     * @param callable $timeoutByProviderResolver Closure(Provider):Duration, calculates timeout (seconds) by provider
     */
    public function scheduleDeadlineTick(string $sessionKey, array $accountStates, callable $timeoutByProviderResolver): void
    {
        foreach (
            it($accountStates)
            ->reindex(function (AccountState $accountState) use ($timeoutByProviderResolver) {
                $provider = $this->getProviderFromState($accountState);
                $timeout = $timeoutByProviderResolver($provider)->getAsSecondsInt();
                $coalescingInterval = $this->NEXT_TICK_SCHEDULER_COALESCING_INTERVAL->getAsSecondsInt();

                return (int) (\ceil($timeout / $coalescingInterval) * $coalescingInterval);
            })
            ->collapseByKey() as $delaySeconds => $_
        ) {
            $this->scheduleTick(
                new UpdaterSessionTimeoutTickTask($sessionKey),
                seconds($delaySeconds)
            );
        }
    }

    public function scheduleTick(UpdaterSessionTickTask $task, Duration $delay): void
    {
        $this->logger->info(
            "scheduling session tick for {$delay}",
            [
                'updater_session_key' => $task->sessionKey,
                'task_class' => \get_class($task),
            ]
        );
        $this->asyncProcess->execute($task, $delay->getAsSecondsInt(), false, Process::PRIORITY_HIGH);
    }

    public function scheduleTickByHttpRequest(string $sessionKey, Request $request, UserMessagesHandlerResult $userMessagesHandlerResult): void
    {
        $userMessagesHandlerResult->addAccounts = \array_slice($userMessagesHandlerResult->addAccounts, 0, UpdaterSessionManager::MAX_ADDED_ACCOUNTS_COUNT);
        $updaterTask = new UpdaterSessionTickTask(
            $sessionKey,
            $userMessagesHandlerResult,
            $this->requestSerializer->serializeRequest($request)
        );
        $this->asyncProcess->execute($updaterTask, null, false, Process::PRIORITY_HIGH);
    }
}
