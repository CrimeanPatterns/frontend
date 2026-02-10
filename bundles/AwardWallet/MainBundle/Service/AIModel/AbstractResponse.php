<?php

namespace AwardWallet\MainBundle\Service\AIModel;

abstract class AbstractResponse implements ResponseInterface
{
    protected ?array $rawResponse;

    protected ?string $content;

    protected int $httpStatusCode;

    protected ?int $promptTokens;

    protected ?int $completionTokens;

    protected ?float $cost;

    public function __construct(
        ?array $rawResponse,
        ?string $content,
        int $httpStatusCode,
        ?int $promptTokens,
        ?int $completionTokens,
        ?float $cost
    ) {
        $this->rawResponse = $rawResponse;
        $this->content = $content;
        $this->httpStatusCode = $httpStatusCode;
        $this->promptTokens = $promptTokens;
        $this->completionTokens = $completionTokens;
        $this->cost = $cost;
    }

    public function getRawResponse(): ?array
    {
        return $this->rawResponse;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function isHttpStatusCodeSuccessful(): bool
    {
        return $this->httpStatusCode >= 200 && $this->httpStatusCode < 300;
    }

    public function getPromptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): ?int
    {
        return $this->completionTokens;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }
}
