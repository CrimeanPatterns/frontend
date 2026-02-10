<?php

namespace AwardWallet\MainBundle\Service\AIModel\OpenAI;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\AIModel\AbstractRequest;

/**
 * @NoDI
 */
class Request extends AbstractRequest
{
    public const MODEL_CHATGPT_4O_LATEST = 'chatgpt-4o-latest';

    public const MODEL_CHATGPT_35_TURBO = 'gpt-3.5-turbo-0125';

    public function __construct(string $prompt, array $options = [])
    {
        parent::__construct($prompt, array_merge([
            'model' => self::MODEL_CHATGPT_4O_LATEST,
        ], $options));
    }

    /**
     * Set a system message for this request.
     *
     * @param string $systemMessage The system message to set
     */
    public function withSystemMessage(string $systemMessage): self
    {
        $options = $this->options;
        $options['system_message'] = $systemMessage;

        $new = clone $this;
        $new->options = $options;

        return $new;
    }

    /**
     * Set the model for this request.
     *
     * @param string $model The model to use (e.g., "gpt-4", "gpt-3.5-turbo")
     */
    public function withModel(string $model): self
    {
        $options = $this->options;
        $options['model'] = $model;

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

    /**
     * Set response format to JSON object.
     */
    public function withJsonResponse(): self
    {
        $options = $this->options;
        $options['response_json'] = true;

        $new = clone $this;
        $new->options = $options;

        return $new;
    }
}
