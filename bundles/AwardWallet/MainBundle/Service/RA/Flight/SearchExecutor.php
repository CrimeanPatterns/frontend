<?php

namespace AwardWallet\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class SearchExecutor implements ExecutorInterface
{
    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    /**
     * @param SearchTask $task
     */
    public function execute(Task $task, $delay = null)
    {
        try {
            $this->api->search($task->getQueryId());
        } catch (SearchException $e) {
        }

        return new Response();
    }
}
