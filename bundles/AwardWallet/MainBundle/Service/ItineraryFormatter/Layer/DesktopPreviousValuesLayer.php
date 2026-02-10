<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Combinator\CompositionLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Combinator\MergeLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Factory\CurrencyStringLayerFactory;

class DesktopPreviousValuesLayer implements DILayerInterface
{
    use CachedLayerTrait;

    private BaseStringLayer $baseStringLayer;

    private DesktopLayer $desktopLayer;

    private PreviousValuesLayer $previousValuesLayer;

    private CurrencyStringLayerFactory $currencyLayerFactory;

    private ?CurrentValuesLayer $baseEncodersLayer;

    public function __construct(
        BaseStringLayer $baseStringLayer,
        PreviousValuesLayer $previousValuesLayer,
        DesktopLayer $desktopLayer,
        CurrencyStringLayerFactory $currencyLayerFactory
    ) {
        $this->baseStringLayer = $baseStringLayer;
        $this->desktopLayer = $desktopLayer;
        $this->previousValuesLayer = $previousValuesLayer;
        $this->currencyLayerFactory = $currencyLayerFactory;
    }

    protected function doGetEncodersMap(array $previousEncodersMap = []): array
    {
        return
            (new CompositionLayer(
                $this->previousValuesLayer,
                new MergeLayer(
                    $this->baseStringLayer,
                    $this->desktopLayer
                ),
                $this->currencyLayerFactory->make(PreviousValuesLayer::class, CurrentValuesLayer::class)
            ))->getEncodersMap();
    }
}
