<?php

namespace AwardWallet\MainBundle\Service\AIModel\Deepseek;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\AIModel\AbstractRequest;

/**
 * @NoDI
 */
class Request extends AbstractRequest
{
    public const MODEL_DEEPSEEK_CHAT = 'deepseek-chat';

    public function __construct(string $prompt, array $options = [])
    {
        parent::__construct($prompt, array_merge([
            'model' => self::MODEL_DEEPSEEK_CHAT,
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
     * @param string $model The model to use
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
     * https://api-docs.deepseek.com/quick_start/parameter_settings.
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
