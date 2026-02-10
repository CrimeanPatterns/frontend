<?php

namespace AwardWallet\MainBundle\Service\AccountHistory\Async;

use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use AwardWallet\MainBundle\Service\SocksMessaging\Client as SocksClient;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class TransactionsExistExecutor implements ExecutorInterface
{
    private SocksClient $messaging;

    private SpentAnalysisService $analysisService;

    public function __construct(SocksClient $messaging, SpentAnalysisService $analysisService)
    {
        $this->messaging = $messaging;
        $this->analysisService = $analysisService;
    }

    /**
     * @param TransactionsExistTask $task
     */
    public function execute(Task $task, $delay = null): Response
    {
        $result = $this->analysisService->transactionsExists($task->getSubAccountIds());
        $this->messaging->publish($task->getResponseChannel(), ["type" => "results", "results" => $result]);

        return new Response();
    }
}
