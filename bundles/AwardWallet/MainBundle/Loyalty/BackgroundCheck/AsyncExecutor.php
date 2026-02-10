<?php

namespace AwardWallet\MainBundle\Loyalty\BackgroundCheck;

use AwardWallet\MainBundle\Service\BackgroundCheckUpdater;
use AwardWallet\MainBundle\Worker\AsyncProcess\ExecutorInterface;
use AwardWallet\MainBundle\Worker\AsyncProcess\Response;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class AsyncExecutor implements ExecutorInterface
{
    private BackgroundCheckUpdater $backgroundCheckUpdater;

    public function __construct(BackgroundCheckUpdater $backgroundCheckUpdater)
    {
        $this->backgroundCheckUpdater = $backgroundCheckUpdater;
    }

    /**
     * @param AsyncTask $task
     */
    public function execute(Task $task, $delay = null)
    {
        $this->backgroundCheckUpdater->updateProvider($task->providerId);

        return new Response();
    }
}
