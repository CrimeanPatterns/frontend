<?php

namespace AwardWallet\MainBundle\Service\AIModel\Claude;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\AIModel\AbstractRequest;

/**
 * @NoDI
 */
class Request extends AbstractRequest
{
    public const MODEL_CLAUDE_SONNET_4 = 'claude-sonnet-4-20250514';

    public const MODEL_CLAUDE_OPUS_4 = 'claude-opus-4-20250514';

    public const MODEL_CLAUDE_HAIKU_35 = 'claude-3-5-haiku-20241022';

    public const MODEL_CLAUDE_SONNET_35 = 'claude-3-5-sonnet-20241022';

    public function __construct(string $prompt, array $options = [])
    {
        parent::__construct($prompt, array_merge([
            'model' => self::MODEL_CLAUDE_SONNET_4,
            'max_tokens' => 4096,
        ], $options));
    }

    /**
     * Set a system message for this request.
     */
    public function withSystemMessage(string $systemMessage, bool $cached = true): self
    {
        $options = $this->options;
        $options['system'] = [
            array_merge([
                'type' => 'text',
                'text' => $systemMessage,
            ], $cached ? ['cache_control' => ['type' => 'ephemeral']] : []),
        ];

        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    public function addSystemMessage(string $systemMessage, bool $cached = true): self
    {
        $options = $this->options;

        if (!isset($options['system'])) {
            $options['system'] = [];
        }

        $options['system'][] = array_merge([
            'type' => 'text',
            'text' => $systemMessage,
        ], $cached ? ['cache_control' => ['type' => 'ephemeral']] : []);

        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    /**
     * Set the model for this request.
     */
    public function withModel(string $model): self
    {
        $options = $this->options;
        $options['model'] = $model;

        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    public function withMaxTokens(int $maxTokens): self
    {
        $options = $this->options;
        $options['max_tokens'] = $maxTokens;

        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    /**
     * Set the temperature for this request.
     *
     * @param float $temperature Temperature value (0.0 to 1.0)
     */
    public function withTemperature(float $temperature): self
    {
        $options = $this->options;
        $options['temperature'] = $temperature;

        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    public function withJsonResponse(): self
    {
        return $this->addSystemMessage('You must respond with ONLY valid JSON. No additional text or explanations.');
    }
}
