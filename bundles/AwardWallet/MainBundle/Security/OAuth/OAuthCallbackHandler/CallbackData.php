<?php

namespace AwardWallet\MainBundle\Security\OAuth\OAuthCallbackHandler;

use AwardWallet\MainBundle\Security\OAuth\ExchangeCodeRequest\ExchangeCodeRequest;
use AwardWallet\MainBundle\Security\OAuth\State;

class CallbackData
{
    /**
     * @var ?string
     */
    private $serializedState;
    /**
     * @var ?string
     */
    private $error;
    /**
     * @var ?string
     */
    private $errorDescription;
    /**
     * @var ?string
     */
    private $code;
    /**
     * @var State
     */
    private $state;
    /**
     * @var ExchangeCodeRequest
     */
    private $exchangeCodeRequest;
    private ?array $rawCallbackData = null;

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): CallbackData
    {
        $this->state = $state;

        return $this;
    }

    public function getExchangeCodeRequest(): ExchangeCodeRequest
    {
        return $this->exchangeCodeRequest;
    }

    public function setExchangeCodeRequest(ExchangeCodeRequest $exchangeCodeRequest): CallbackData
    {
        $this->exchangeCodeRequest = $exchangeCodeRequest;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): CallbackData
    {
        $this->code = $code;

        return $this;
    }

    public function getSerializedState(): ?string
    {
        return $this->serializedState;
    }

    public function setSerializedState(?string $serializedState): CallbackData
    {
        $this->serializedState = $serializedState;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): CallbackData
    {
        $this->error = $error;

        return $this;
    }

    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }

    public function setErrorDescription(?string $errorDescription): CallbackData
    {
        $this->errorDescription = $errorDescription;

        return $this;
    }

    public function getRawCallbackData(): ?array
    {
        return $this->rawCallbackData;
    }

    public function setRawCallbackData($rawCallbackData): self
    {
        $this->rawCallbackData = $rawCallbackData;

        return $this;
    }
}
