<?php

namespace AwardWallet\MainBundle\Service\AIModel\OpenAI;

use AwardWallet\MainBundle\Service\AIModel\AbstractProvider;
use AwardWallet\MainBundle\Service\AIModel\Exception\BatchProcessingException;
use AwardWallet\MainBundle\Service\AIModel\Exception\TokenLimitExceededException;
use AwardWallet\MainBundle\Service\AIModel\RequestInterface;
use AwardWallet\MainBundle\Service\AIModel\ResponseInterface;
use AwardWallet\MainBundle\Service\AIModel\TokenCounter;
use Psr\Log\LoggerInterface;

class Provider extends AbstractProvider
{
    private \HttpDriverInterface $httpDriver;

    private string $apiKey;

    public function __construct(
        LoggerInterface $logger,
        \HttpDriverInterface $httpDriver,
        string $openAiApiKey
    ) {
        parent::__construct($logger);

        $this->httpDriver = $httpDriver;
        $this->apiKey = $openAiApiKey;
    }

    public function getName(): string
    {
        return 'openai';
    }

    public function createRequest(string $prompt, array $options = []): RequestInterface
    {
        return new Request($prompt, $options);
    }

    /**
     * @param Request $request
     * @return Response
     */
    protected function doSendRequest(RequestInterface $request): ResponseInterface
    {
        if (!$request instanceof Request) {
            throw new \InvalidArgumentException('Request must be an instance of OpenAIRequest');
        }

        $options = $request->getOptions();

        if (!isset($options['model'])) {
            throw new \InvalidArgumentException('Model not set in request options');
        }

        $payload = [
            'model' => $options['model'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $request->getPrompt(),
                ],
            ],
        ];

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        if (isset($options['response_json']) && $options['response_json']) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        if (isset($options['system_message'])) {
            array_unshift($payload['messages'], [
                'role' => 'system',
                'content' => $options['system_message'],
            ]);
        }

        $httpResponse = $this->httpDriver->request(
            new \HttpDriverRequest(
                'https://api.openai.com/v1/chat/completions',
                'POST',
                json_encode($payload),
                [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                500
            ),
        );

        $decoded = @json_decode($httpResponse->body, true);

        if (is_array($decoded) && $httpResponse->httpCode >= 200 && $httpResponse->httpCode < 300) {
            $content = $decoded['choices'][0]['message']['content'] ?? null;
            $promptTokens = $decoded['usage']['prompt_tokens'] ?? null;
            $completionTokens = $decoded['usage']['completion_tokens'] ?? null;
            $cost = $this->calculateCost($decoded);
        }

        return new Response(
            is_array($decoded) ? $decoded : null,
            $content ?? null,
            $httpResponse->httpCode,
            $promptTokens ?? null,
            $completionTokens ?? null,
            $cost ?? null
        );
    }

    protected function getBatchOptions(string $systemMessage, array $additionalOptions): array
    {
        return array_merge($additionalOptions, [
            'system_message' => $systemMessage,
            'response_json' => true,
        ]);
    }

    protected function estimateTokens(string $item): int
    {
        return TokenCounter::countGptTokens($item);
    }

    /**
     * @param Response $response
     */
    protected function checkResponse(ResponseInterface $response): void
    {
        if ($response->isTruncated()) {
            throw new TokenLimitExceededException('Response was truncated, likely due to token limit exceeded');
        }

        if (!$response->isSuccessfulFinishReason()) {
            throw new BatchProcessingException('AI provider response finish reason is not "stop"', ['finish_reason' => $response->getFinishReason()]);
        }
    }

    private function calculateCost(array $responseData): ?float
    {
        if (!isset($responseData['model'])) {
            return null;
        }

        $pricing = $this->getPrices();
        $model = $responseData['model'];

        if (!isset($pricing[$model])) {
            return null;
        }

        $modelPricing = $pricing[$model];
        $promptTokens = $responseData['usage']['prompt_tokens'] ?? 0;
        $completionTokens = $responseData['usage']['completion_tokens'] ?? 0;
        $inputCost = ($promptTokens / 1_000_000) * $modelPricing['input'];
        $outputCost = ($completionTokens / 1_000_000) * $modelPricing['output'];

        return round($inputCost + $outputCost, 4);
    }

    /**
     * https://platform.openai.com/docs/pricing.
     */
    private function getPrices(): array
    {
        return [
            Request::MODEL_CHATGPT_4O_LATEST => [
                'input' => 5,
                'output' => 15,
            ],
            Request::MODEL_CHATGPT_35_TURBO => [
                'input' => 0.5,
                'output' => 1.5,
            ],
        ];
    }
}
