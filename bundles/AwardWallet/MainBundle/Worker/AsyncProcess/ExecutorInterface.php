<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

interface ExecutorInterface
{
    /**
     * @param int $delay milliseconds delay from previous execution
     * @return Response
     */
    public function execute(Task $task, $delay = null);
}
