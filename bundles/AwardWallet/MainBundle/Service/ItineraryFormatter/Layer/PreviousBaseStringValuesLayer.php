<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Combinator\CompositionLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Factory\CurrencyStringLayerFactory;

class PreviousBaseStringValuesLayer implements DILayerInterface
{
    use CachedLayerTrait;

    private BaseStringLayer $baseStringLayer;

    private PreviousValuesLayer $previousValuesLayer;

    private CurrencyStringLayerFactory $currencyLayerFactory;

    public function __construct(
        BaseStringLayer $baseStringLayer,
        PreviousValuesLayer $previousValuesLayer,
        CurrencyStringLayerFactory $currencyLayerFactory
    ) {
        $this->baseStringLayer = $baseStringLayer;
        $this->previousValuesLayer = $previousValuesLayer;
        $this->currencyLayerFactory = $currencyLayerFactory;
    }

    protected function doGetEncodersMap(array $previousEncodersMap = []): array
    {
        return
            (new CompositionLayer(
                $this->previousValuesLayer,
                $this->baseStringLayer,
                $this->currencyLayerFactory->make(PreviousValuesLayer::class, CurrentValuesLayer::class)
            ))->getEncodersMap();
    }
}
