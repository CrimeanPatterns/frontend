<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

class Request
{
    /**
     * @var Task
     */
    public $task;

    /**
     * @param Task[] $tasks
     */
    public function __construct(array $tasks)
    {
        $this->tasks = $tasks;
    }
}
