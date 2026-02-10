<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Combinator\CompositionLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Combinator\MergeLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Factory\CurrencyStringLayerFactory;

class DesktopCurrentValuesLayer implements DILayerInterface
{
    use CachedLayerTrait;

    private BaseStringLayer $baseStringLayer;

    private CurrentValuesLayer $baseEncodersLayer;

    private DesktopLayer $desktopLayer;

    private CurrencyStringLayerFactory $currencyLayerFactory;

    public function __construct(
        BaseStringLayer $baseStringLayer,
        CurrentValuesLayer $baseEncodersLayer,
        DesktopLayer $desktopLayer,
        CurrencyStringLayerFactory $currencyLayerFactory
    ) {
        $this->baseStringLayer = $baseStringLayer;
        $this->baseEncodersLayer = $baseEncodersLayer;
        $this->desktopLayer = $desktopLayer;
        $this->currencyLayerFactory = $currencyLayerFactory;
    }

    protected function doGetEncodersMap(array $previousEncodersMap = []): array
    {
        return
            (new CompositionLayer(
                $this->baseEncodersLayer,
                new MergeLayer(
                    $this->baseStringLayer,
                    $this->desktopLayer
                ),
                $this->currencyLayerFactory->make(CurrentValuesLayer::class)
            ))->getEncodersMap();
    }
}
