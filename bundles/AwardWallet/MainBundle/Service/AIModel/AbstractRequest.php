<?php

namespace AwardWallet\MainBundle\Service\AIModel;

abstract class AbstractRequest implements RequestInterface
{
    protected string $prompt;

    protected array $options;

    /**
     * @param string $prompt The prompt/text to send
     * @param array $options Additional options
     */
    public function __construct(string $prompt, array $options = [])
    {
        $this->prompt = $prompt;
        $this->options = $options;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
