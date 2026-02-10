<?php

namespace AwardWallet\MainBundle\Service\AIModel;

use AwardWallet\MainBundle\Service\AIModel\Exception\BatchProcessingException;
use AwardWallet\MainBundle\Service\AIModel\Exception\TokenLimitExceededException;
use AwardWallet\MainBundle\Service\LogProcessor;
use Monolog\Handler\PsrHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = new Logger('ai_model', [new PsrHandler($logger)], [new LogProcessor('ai_model')]);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            $this->logger->info(sprintf('sending request to "%s" provider', $this->getName()));

            $response = $this->doSendRequest($request);

            $this->logger->info(sprintf('received response from "%s" provider', $this->getName()), [
                'http_code' => $response->getHttpStatusCode(),
                'prompt' => mb_substr($request->getPrompt(), 0, 255),
                'cost' => $response->getCost(),
                'prompt_tokens' => $response->getPromptTokens(),
                'response_tokens' => $response->getCompletionTokens(),
            ]);

            return $response;
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'error sending request to "%s" provider: %s',
                $this->getName(),
                $e->getMessage()
            ), [
                'exception' => $e,
            ]);

            throw $e;
        }
    }

    public function sendBatchJsonRequest(
        string $systemMessage,
        array $data,
        ?BatchConfig $batchConfig = null,
        array $additionalOptions = []
    ): BatchProcessingResult {
        $config = $batchConfig ?? BatchConfig::create();
        $options = $this->getBatchOptions($systemMessage, $additionalOptions);
        $batches = $this->createBatches($data, $config);
        $totalBatches = count($batches);
        $providerName = $this->getName();
        $stats = [];
        $results = [];

        $this->logger->info(sprintf(
            'processing %d batches with %d items each, total items: %d, provider: %s',
            $totalBatches,
            $config->getBatchSize(),
            count($data),
            $providerName
        ));

        try {
            foreach ($batches as $batchIndex => $batch) {
                $this->logger->info(sprintf(
                    'processing batch %d of %d, items: %d',
                    $batchIndex + 1,
                    $totalBatches,
                    count($batch)
                ));

                $batchResult = $this->processBatchWithRetry($batch, $options, $config, $stats);
                $results = array_merge($results, $batchResult);
            }

            $this->logger->info(sprintf(
                'processed %d batches, total cost: %.4f, request count: %d',
                $totalBatches,
                $stats[$providerName]['cost'] ?? 0,
                $stats[$providerName]['requests'] ?? 0
            ));

            return new BatchProcessingResult($results, $stats);
        } catch (BatchProcessingException $e) {
            $e->setResult(new BatchProcessingResult($results, $stats));

            throw $e;
        }
    }

    /**
     * Actually send the request to the provider.
     * To be implemented by concrete provider classes.
     */
    abstract protected function doSendRequest(RequestInterface $request): ResponseInterface;

    abstract protected function getBatchOptions(string $systemMessage, array $additionalOptions): array;

    abstract protected function estimateTokens(string $item): int;

    abstract protected function checkResponse(ResponseInterface $response): void;

    private function processBatchWithRetry(array $batch, array $options, BatchConfig $config, array &$stats): array
    {
        $attempt = 0;
        $maxRetries = $config->getMaxRetries();

        while ($attempt <= $maxRetries) {
            try {
                return $this->processSingleBatch($batch, $options, $stats);
            } catch (\Throwable $e) {
                if ($e instanceof BatchProcessingException) {
                    throw $e;
                }

                $attempt++;

                if ($attempt > $maxRetries) {
                    throw new BatchProcessingException(sprintf('Batch processing failed after %d retries', $maxRetries), ['error' => $e->getMessage()], 0, $e);
                }

                $delay = $config->getRetryDelayBase() ** $attempt;
                $this->logger->warning(sprintf(
                    'Batch processing failed, attempt %d/%d, retrying in %d seconds: %s',
                    $attempt,
                    $maxRetries,
                    $delay,
                    $e->getMessage()
                ));

                sleep($delay);

                // Split batch in half if context/length issues detected from response
                if ($e instanceof TokenLimitExceededException && count($batch) > 1) {
                    $this->logger->info('splitting batch due to token limit exceeded');
                    $halfSize = ceil(count($batch) / 2);
                    $firstHalf = array_slice($batch, 0, $halfSize, true);
                    $secondHalf = array_slice($batch, $halfSize, null, true);

                    $results = [];
                    $results = array_merge($results, $this->processBatchWithRetry($firstHalf, $options, $config, $stats));
                    $results = array_merge($results, $this->processBatchWithRetry($secondHalf, $options, $config, $stats));

                    return $results;
                }
            }
        }

        throw new BatchProcessingException('Unexpected error in retry logic');
    }

    private function processSingleBatch(array $batch, array $options, array &$stats): array
    {
        $response = $this->sendRequest($this->createRequest(json_encode($batch), $options));
        $providerName = $this->getName();

        if (!isset($stats[$providerName])) {
            $stats[$providerName] = [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'cost' => 0.0,
                'requests' => 0,
            ];
        }

        $stats[$providerName]['prompt_tokens'] += $response->getPromptTokens() ?? 0;
        $stats[$providerName]['completion_tokens'] += $response->getCompletionTokens() ?? 0;
        $stats[$providerName]['cost'] += $response->getCost() ?? 0.0;
        ++$stats[$providerName]['requests'];

        if (!$response->isHttpStatusCodeSuccessful()) {
            throw new \RuntimeException('HTTP error from AI provider');
        }

        if (!is_array($response->getRawResponse())) {
            throw new \RuntimeException('AI provider response is not an array');
        }

        $this->checkResponse($response);
        $responseJson = $response->getContent();

        if (is_null($responseJson)) {
            throw new \RuntimeException('AI provider response is null');
        }

        $json = json_decode($responseJson, true);

        if (!is_array($json)) {
            // attempt to extract JSON from string if direct decoding fails
            $json = $this->extractJsonFromString($responseJson);
        }

        if (!is_array($json)) {
            throw new BatchProcessingException('AI provider response is not an array');
        }

        return $json;
    }

    private function createBatches(array $data, BatchConfig $config): array
    {
        $batches = [];
        $currentBatch = [];
        $currentTokens = 0;

        foreach ($data as $key => $item) {
            $itemTokens = $this->estimateTokens(
                is_array($item) ? json_encode($item) : $item,
            );

            if (
                ($currentTokens + $itemTokens) > $config->getMaxTokensPerBatch()
                || count($currentBatch) >= $config->getBatchSize()
            ) {
                if (!empty($currentBatch)) {
                    $batches[] = $currentBatch;
                    $currentBatch = [];
                    $currentTokens = 0;
                }
            }

            $currentBatch[$key] = $item;
            $currentTokens += $itemTokens;
        }

        if (!empty($currentBatch)) {
            $batches[] = $currentBatch;
        }

        return $batches;
    }

    private function extractJsonFromString(string $text): ?array
    {
        if (preg_match('/({.*?}|\\[.*?\\])/ims', $text, $matches)) {
            $json = json_decode($matches[0], true);

            return json_last_error() === JSON_ERROR_NONE ? $json : null;
        }

        return null;
    }
}
