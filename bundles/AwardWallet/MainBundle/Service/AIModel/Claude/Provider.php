<?php

namespace AwardWallet\MainBundle\Service\AIModel\Claude;

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
        string $claudeKey
    ) {
        parent::__construct($logger);

        $this->httpDriver = $httpDriver;
        $this->apiKey = $claudeKey;
    }

    public function getName(): string
    {
        return 'claude';
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
            throw new \InvalidArgumentException('Request must be an instance of Claude Request');
        }

        $options = $request->getOptions();

        if (!isset($options['model'])) {
            throw new \InvalidArgumentException('Model not set in request options');
        }

        $payload = [
            'model' => $options['model'],
            'max_tokens' => $options['max_tokens'] ?? 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $request->getPrompt(),
                ],
            ],
        ];

        if (isset($options['system'])) {
            $payload['system'] = $options['system'];
        }

        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }

        $httpResponse = $this->httpDriver->request(
            new \HttpDriverRequest(
                'https://api.anthropic.com/v1/messages',
                'POST',
                json_encode($payload),
                [
                    'Content-Type' => 'application/json',
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                ],
                500
            ),
        );

        $decoded = @json_decode($httpResponse->body, true);

        if (is_array($decoded) && $httpResponse->httpCode >= 200 && $httpResponse->httpCode < 300) {
            $content = $decoded['content'][0]['text'] ?? null;
            $promptTokens = ($decoded['usage']['input_tokens'] ?? 0)
                + ($decoded['usage']['cache_read_input_tokens'] ?? 0)
                + ($decoded['usage']['cache_creation_input_tokens'] ?? 0);
            $completionTokens = $decoded['usage']['output_tokens'] ?? null;
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
            'system' => [
                [
                    'type' => 'text',
                    'text' => $systemMessage,
                    'cache_control' => [
                        'type' => 'ephemeral', // Use ephemeral cache by default
                    ],
                ],
            ],
        ]);
    }

    protected function estimateTokens(string $item): int
    {
        return TokenCounter::countClaudeTokens($item);
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
            throw new BatchProcessingException('Claude provider response finish reason is not "end_turn"', ['finish_reason' => $response->getFinishReason()]);
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
        $promptTokens = $responseData['usage']['input_tokens'] ?? 0;
        $completionTokens = $responseData['usage']['output_tokens'] ?? 0;

        // Calculate base cost
        $inputCost = ($promptTokens / 1_000_000) * $modelPricing['input'];
        $outputCost = ($completionTokens / 1_000_000) * $modelPricing['output'];

        // Handle cached tokens (they have different pricing)
        $cachedTokens = $responseData['usage']['cache_read_input_tokens'] ?? 0;
        $cacheWriteTokens = $responseData['usage']['cache_creation_input_tokens'] ?? 0;

        if ($cachedTokens > 0) {
            // Cached read tokens are 90% cheaper than regular input tokens
            $cachedReadCost = ($cachedTokens / 1_000_000) * ($modelPricing['input'] * 0.1);
            $inputCost -= ($cachedTokens / 1_000_000) * $modelPricing['input']; // Remove from regular cost
            $inputCost += $cachedReadCost; // Add discounted cost
        }

        if ($cacheWriteTokens > 0) {
            // Cache write tokens are charged at 25% premium over regular input tokens
            $cacheWriteCost = ($cacheWriteTokens / 1_000_000) * ($modelPricing['input'] * 1.25);
            $inputCost += $cacheWriteCost;
        }

        return round($inputCost + $outputCost, 4);
    }

    /**
     * https://www.anthropic.com/pricing.
     */
    private function getPrices(): array
    {
        return [
            Request::MODEL_CLAUDE_SONNET_4 => [
                'input' => 3.0,
                'output' => 15.0,
            ],
            Request::MODEL_CLAUDE_OPUS_4 => [
                'input' => 15.0,
                'output' => 75.0,
            ],
            Request::MODEL_CLAUDE_HAIKU_35 => [
                'input' => 0.8,
                'output' => 4.0,
            ],
            Request::MODEL_CLAUDE_SONNET_35 => [
                'input' => 3.0,
                'output' => 15.0,
            ],
        ];
    }
}
