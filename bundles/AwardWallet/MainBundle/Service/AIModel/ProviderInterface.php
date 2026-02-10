<?php

namespace AwardWallet\MainBundle\Service\AIModel;

interface ProviderInterface
{
    /**
     * Send a request to the language model and get a response.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;

    public function sendBatchJsonRequest(
        string $systemMessage,
        array $data,
        ?BatchConfig $batchConfig = null,
        array $additionalOptions = []
    ): BatchProcessingResult;

    public function createRequest(string $prompt, array $options = []): RequestInterface;

    public function getName(): string;
}
