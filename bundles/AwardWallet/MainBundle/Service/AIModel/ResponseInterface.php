<?php

namespace AwardWallet\MainBundle\Service\AIModel;

interface ResponseInterface
{
    /**
     * Get the raw response data.
     */
    public function getRawResponse(): ?array;

    /**
     * Get the content/text response from the language model.
     */
    public function getContent(): ?string;

    /**
     * Get the HTTP status code of the response.
     */
    public function getHttpStatusCode(): int;

    /**
     * Check if the HTTP status code is successful.
     */
    public function isHttpStatusCodeSuccessful(): bool;

    /**
     * Get the number of tokens used for the prompt.
     */
    public function getPromptTokens(): ?int;

    /**
     * Get the number of tokens used for the completion.
     */
    public function getCompletionTokens(): ?int;

    /**
     * Get the cost (USD) of the request and response.
     */
    public function getCost(): ?float;
}
