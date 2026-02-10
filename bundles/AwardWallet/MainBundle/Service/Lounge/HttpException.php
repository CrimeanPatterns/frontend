<?php

namespace AwardWallet\MainBundle\Service\Lounge;

class HttpException extends \Exception
{
    private array $context;

    public function __construct(string $message, array $context = [])
    {
        parent::__construct($message, $context['httpCode'] ?? 0);

        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
