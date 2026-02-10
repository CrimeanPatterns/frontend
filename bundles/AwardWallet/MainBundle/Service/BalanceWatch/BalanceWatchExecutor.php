<?php

namespace AwardWallet\MainBundle\Service\BalanceWatch;

use AwardWallet\MainBundle\Service\BalanceWatch\Model\BalanceWatchTask;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;
use Doctrine\ORM\EntityManagerInterface;

class BalanceWatchExecutor implements ExecutorInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var BalanceWatchCommand */
    private $balanceWatchCommand;

    public function __construct(
        EntityManagerInterface $entityManager,
        BalanceWatchCommand $balanceWatchCommand
    ) {
        $this->entityManager = $entityManager;
        $this->balanceWatchCommand = $balanceWatchCommand;
    }

    /**
     * @param BalanceWatchTask $task
     * @param int          $delay
     */
    public function execute(Task $task, $delay = null): Response
    {
        $account = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($task->accountId);

        if (null !== $account) {
            $this->balanceWatchCommand->checkAccount($account);
        }

        return new Response();
    }
}
