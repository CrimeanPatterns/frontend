<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Combinator\CompositionLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Factory\CurrencyStringLayerFactory;

class CurrentBaseStringValuesLayer implements DILayerInterface
{
    use CachedLayerTrait;

    private BaseStringLayer $baseStringLayer;

    private CurrentValuesLayer $baseEncodersLayer;

    private CurrencyStringLayerFactory $currencyLayerFactory;

    public function __construct(
        BaseStringLayer $baseStringLayer,
        CurrentValuesLayer $baseEncodersLayer,
        CurrencyStringLayerFactory $currencyLayerFactory
    ) {
        $this->baseStringLayer = $baseStringLayer;
        $this->baseEncodersLayer = $baseEncodersLayer;
        $this->currencyLayerFactory = $currencyLayerFactory;
    }

    protected function doGetEncodersMap(array $previousEncodersMap = []): array
    {
        return
            (new CompositionLayer(
                $this->baseEncodersLayer,
                $this->baseStringLayer,
                $this->currencyLayerFactory->make(CurrentValuesLayer::class)
            ))->getEncodersMap();
    }
}
