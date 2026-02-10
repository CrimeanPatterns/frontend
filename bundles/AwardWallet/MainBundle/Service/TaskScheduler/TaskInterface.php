<?php

namespace AwardWallet\MainBundle\Service\TaskScheduler;

interface TaskInterface
{
    public function getServiceId(): string;

    public function getRequestId(): string;

    public function getMaxRetriesCount(): int;

    public function getCurrentRetriesCount(): int;

    public function incrementRetriesCount(): void;
}
