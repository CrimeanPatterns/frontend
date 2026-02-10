<?php

namespace AwardWallet\MainBundle\Service\RA\Async;

use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class AwardPriceTask extends Task
{
    public const AWARD_PRICE_KEY = 'award_price_task_queued_key_1';

    /** @var array */
    private $params;
    /** @var string */
    private $fileName;
    /** @var string */
    private $message;
    /** @var string */
    private $email;

    public function __construct(array $params, string $fileName, string $message, $email)
    {
        parent::__construct(AwardPriceExecutor::class, bin2hex(random_bytes(10)));

        $this->params = $params;
        $this->fileName = $fileName;
        $this->message = $message;
        $this->email = $email;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
