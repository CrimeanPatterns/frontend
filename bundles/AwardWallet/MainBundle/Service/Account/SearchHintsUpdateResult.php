<?php

namespace AwardWallet\MainBundle\Service\Account;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class SearchHintsUpdateResult
{
    private string $message;
    private array $data;

    public function __construct(string $message, array $data = [])
    {
        $this->message = $message;
        $this->data = $data;
    }

    /**
     * Получить сообщение о статусе обновления.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Получить список подсказок. В случае, если обновление не произошло, вернётся пустой массив.
     */
    public function getData(): array
    {
        return $this->data;
    }
}
