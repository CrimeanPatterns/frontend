<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\History\PlanLinker;

class DataSourceFactory
{
    private $dataSources = [];

    public function __construct(iterable $dataSources)
    {
        foreach ($dataSources as $dataSource) {
            /** @var DataSourceInterface $dataSource */
            foreach ($dataSource->getProviderCodes() as $providerCode) {
                if (isset($this->dataSources[$providerCode])) {
                    throw new \Exception("multiple data sources for provider " . $providerCode);
                }
                $this->dataSources[$providerCode] = $dataSource;
            }
        }
    }

    public function getDataSource(string $providerCode): ?DataSourceInterface
    {
        return $this->dataSources[$providerCode] ?? null;
    }
}
