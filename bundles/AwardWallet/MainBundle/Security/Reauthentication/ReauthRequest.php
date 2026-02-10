<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

class ReauthRequest
{
    /**
     * @var string
     */
    private $action;

    /**
     * @var ?string
     */
    private $context;

    /**
     * @var string
     */
    private $input;

    /**
     * @var ?string
     */
    private $intent;

    public function __construct(?string $action, ?string $context, ?string $input, ?string $intent)
    {
        $this->action = (string) $action;
        $this->context = $context;
        $this->input = (string) $input;
        $this->intent = $intent;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getInput(): string
    {
        return $this->input;
    }

    public function getIntent(): ?string
    {
        return $this->intent;
    }

    public function haveIntent(): bool
    {
        return !empty($this->intent);
    }
}
