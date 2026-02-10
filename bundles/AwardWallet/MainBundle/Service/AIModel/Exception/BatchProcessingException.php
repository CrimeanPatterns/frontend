<?php

namespace AwardWallet\MainBundle\Service\AIModel\Exception;

use AwardWallet\MainBundle\Service\AIModel\BatchProcessingResult;

class BatchProcessingException extends \Exception
{
    private array $context;

    private ?BatchProcessingResult $result = null;

    public function __construct(string $message, array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setResult(BatchProcessingResult $result): void
    {
        $this->result = $result;
    }

    public function getResult(): ?BatchProcessingResult
    {
        return $this->result;
    }
}
