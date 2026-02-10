<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Updater\UpdaterSessionManager;
use AwardWallet\MainBundle\Updater\UpdaterSessionStorage;
use AwardWallet\MainBundle\Updater\UserMessagesHandlerResult;
use Psr\Log\LoggerInterface;

class UpdaterTaskExecutor implements ExecutorInterface
{
    private Process $process;
    private UpdaterSessionManager $updaterSessionManager;
    private SafeExecutorFactory $safeExecutorFactory;
    private UpdaterSessionStorage $updaterSessionStorage;
    private LoggerInterface $logger;
    private AccountRepository $accountRepository;

    public function __construct(
        Process $process,
        UpdaterSessionManager $updaterSessionManager,
        UpdaterSessionStorage $updaterSessionStorage,
        AccountRepository $accountRepository,
        SafeExecutorFactory $safeExecutorFactory,
        LoggerInterface $logger
    ) {
        $this->process = $process;
        $this->updaterSessionManager = $updaterSessionManager;
        $this->safeExecutorFactory = $safeExecutorFactory;
        $this->updaterSessionStorage = $updaterSessionStorage;
        $this->logger = $logger;
        $this->accountRepository = $accountRepository;
    }

    public function execute(Task $task, $delay = null): Response
    {
        if ($task instanceof UpdaterAccountTickTask) {
            return $this->executeAccount($task);
        } elseif ($task instanceof UpdaterSessionTickTask) {
            return $this->executeSession($task);
        }

        return new Response();
    }

    protected function executeAccount(UpdaterAccountTickTask $task): Response
    {
        $successResponse =
            $this->safeExecutorFactory
            ->make(fn () => $this->doExecuteAccount($task))
            ->runOrNull();

        if ($successResponse) {
            return $successResponse;
        }

        throw new TaskNeedsRetryException(\pow(2, $task->retry));
    }

    protected function doExecuteAccount(UpdaterAccountTickTask $task): Response
    {
        $stubResponse = new Response();
        $stubResponse->status = Response::STATUS_READY;
        $account = $this->accountRepository->find($task->accountId);

        if (!$account) {
            return $stubResponse;
        }

        $sessionsList = \array_keys($this->updaterSessionStorage->loadSessionsMapByAccount($account->getId()));
        $this->logger->info(
            \sprintf('%d active session(s) found', \count($sessionsList)),
            [
                'accountid' => $account->getId(),
                'updater_session_keys' => $sessionsList,
            ]
        );

        foreach ($sessionsList as $sessionKey) {
            $this->process->execute(new UpdaterSessionTickTask($sessionKey), null, false, Process::PRIORITY_HIGH);
        }

        return $stubResponse;
    }

    protected function executeSession(UpdaterSessionTickTask $task): Response
    {
        if ($task->addAccounts || $task->addAccounts) {
            $userMessagesRes = new UserMessagesHandlerResult();
            $userMessagesRes->addAccounts = $task->addAccounts;
            $userMessagesRes->removeAccounts = $task->removeAccounts;
        } else {
            $userMessagesRes = $task->userMessagesHandlerResult;
        }

        $lockWasAcquired = $this->updaterSessionManager->synchronizedTick(
            $task->sessionKey,
            $userMessagesRes,
            $task->serializedHttpRequest
        );

        if (!$lockWasAcquired) {
            throw new TaskNeedsRetryException(5);
        }

        $response = new Response();
        $response->status = Response::STATUS_READY;

        return $response;
    }
}
