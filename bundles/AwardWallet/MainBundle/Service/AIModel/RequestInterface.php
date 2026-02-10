<?php

namespace AwardWallet\MainBundle\Service\AIModel;

interface RequestInterface
{
    /**
     * Get the prompt/text to send to the language model.
     *
     * @return string The prompt text
     */
    public function getPrompt(): string;

    /**
     * Get additional options for the request.
     *
     * @return array Additional options specific to the provider
     */
    public function getOptions(): array;
}
