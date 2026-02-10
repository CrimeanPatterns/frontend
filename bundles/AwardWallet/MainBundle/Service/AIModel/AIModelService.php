<?php

namespace AwardWallet\MainBundle\Service\AIModel;

class AIModelService
{
    /**
     * @var ProviderInterface[]
     */
    private array $providers = [];

    public function __construct(iterable $aiProviders)
    {
        foreach ($aiProviders as $provider) {
            $this->providers[$provider->getName()] = $provider;
        }
    }

    public function getProvider(string $providerName): ProviderInterface
    {
        if (!isset($this->providers[$providerName])) {
            throw new \InvalidArgumentException(sprintf('Provider not found: "%s"', $providerName));
        }

        return $this->providers[$providerName];
    }

    public function sendPrompt(string $prompt, string $providerName, array $options = []): ResponseInterface
    {
        $provider = $this->getProvider($providerName);
        $request = $provider->createRequest($prompt, $options);

        return $provider->sendRequest($request);
    }

    public function sendBatchJsonRequest(
        string $systemMessage,
        array $data,
        string $providerName,
        ?BatchConfig $batchConfig = null,
        array $additionalOptions = []
    ): BatchProcessingResult {
        $provider = $this->getProvider($providerName);

        return $provider->sendBatchJsonRequest($systemMessage, $data, $batchConfig, $additionalOptions);
    }

    /**
     * Get all registered providers.
     *
     * @return ProviderInterface[] The providers
     */
    public function getProviders(): array
    {
        return $this->providers;
    }
}
