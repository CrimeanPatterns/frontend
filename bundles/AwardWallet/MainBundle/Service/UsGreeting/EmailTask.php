<?php

namespace AwardWallet\MainBundle\Service\UsGreeting;

use AwardWallet\MainBundle\Service\TaskScheduler\Task;

class EmailTask extends Task
{
    private int $userId;

    private string $emailClass;

    private bool $skipDoNotSend;

    /**
     * @var int timestamp
     */
    private int $deadline;

    public function __construct(int $userId, string $emailClass, bool $skipDoNotSend, int $deadline)
    {
        parent::__construct(EmailConsumer::class, $this->generateRequestId($emailClass));

        $this->userId = $userId;
        $this->emailClass = $emailClass;
        $this->skipDoNotSend = $skipDoNotSend;
        $this->deadline = $deadline;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getEmailClass(): string
    {
        return $this->emailClass;
    }

    public function getSkipDoNotSend(): bool
    {
        return $this->skipDoNotSend;
    }

    public function getDeadline(): int
    {
        return $this->deadline;
    }

    private function generateRequestId(string $emailClass): string
    {
        $emailClass = strtolower(preg_replace(
            '/(?<!^)[A-Z]/',
            '_$0',
            (new \ReflectionClass($emailClass))->getShortName()
        ));

        return sprintf(
            '%s_%s',
            $emailClass,
            bin2hex(random_bytes(10))
        );
    }
}
