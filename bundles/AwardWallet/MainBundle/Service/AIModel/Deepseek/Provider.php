<?php

namespace AwardWallet\MainBundle\Service\AIModel\Deepseek;

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
        string $deepseekApiKey
    ) {
        parent::__construct($logger);

        $this->httpDriver = $httpDriver;
        $this->apiKey = $deepseekApiKey;
    }

    public function getName(): string
    {
        return 'deepseek';
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
            throw new \InvalidArgumentException('Request must be an instance of DeepseekRequest');
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
                'https://api.deepseek.com/chat/completions',
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
        return TokenCounter::countDeepSeekTokens($item);
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

        if (!isset($responseData['created'])) {
            return null;
        }

        $pricing = $this->getPrices();
        $model = $responseData['model'];

        if (!isset($pricing[$model])) {
            return null;
        }

        $modelPricing = $pricing[$model];
        // during 16:30-00:30 UTC each day
        $discountTime = $this->isDiscountTime($responseData['created']);
        $subPricing = $discountTime ? 'discount' : 'regular';
        $promptCacheHitTokens = $responseData['usage']['prompt_cache_hit_tokens'] ?? 0;
        $promptCacheMissTokens = $responseData['usage']['prompt_cache_miss_tokens'] ?? 0;
        $completionTokens = $responseData['usage']['completion_tokens'] ?? 0;

        $inputCost = ($promptCacheHitTokens / 1_000_000) * $modelPricing['input'][$subPricing]['cache_hit'] +
            ($promptCacheMissTokens / 1_000_000) * $modelPricing['input'][$subPricing]['cache_miss'];
        $outputCost = ($completionTokens / 1_000_000) * $modelPricing['output'][$subPricing];

        return round($inputCost + $outputCost, 5);
    }

    private function isDiscountTime(int $requestTs): bool
    {
        $now = new \DateTimeImmutable(
            '@' . $requestTs,
            new \DateTimeZone('UTC')
        );

        $midnightStart = $now->setTime(0, 0, 0);
        $midnightEnd = $now->setTime(0, 30, 0);

        $afternoonStart = $now->setTime(16, 30, 0);
        $afternoonEnd = $now->setTime(23, 59, 59);

        return ($now >= $midnightStart && $now < $midnightEnd)
            || ($now >= $afternoonStart && $now <= $afternoonEnd);
    }

    /**
     * https://api-docs.deepseek.com/quick_start/pricing.
     */
    private function getPrices(): array
    {
        return [
            Request::MODEL_DEEPSEEK_CHAT => [
                'input' => [
                    'regular' => [
                        'cache_hit' => 0.07,
                        'cache_miss' => 0.27,
                    ],
                    'discount' => [
                        'cache_hit' => 0.035,
                        'cache_miss' => 0.135,
                    ],
                ],
                'output' => [
                    'regular' => 1.10,
                    'discount' => 0.55,
                ],
            ],
        ];
    }
}
