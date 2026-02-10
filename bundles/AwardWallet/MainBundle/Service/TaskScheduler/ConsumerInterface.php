<?php

namespace AwardWallet\MainBundle\Service\TaskScheduler;

interface ConsumerInterface
{
    public function consume(TaskInterface $task): void;
}
