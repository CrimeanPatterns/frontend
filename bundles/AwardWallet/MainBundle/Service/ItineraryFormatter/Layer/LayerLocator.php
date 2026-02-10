<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class LayerLocator
{
    /**
     * @var iterable
     */
    private $layersIterable;
    /**
     * @var LayerInterface[]
     */
    private $layersByClassMap;

    public function __construct(iterable $layersIterable)
    {
        $this->layersIterable = $layersIterable;
    }

    public function getLayer(string $class): LayerInterface
    {
        if (!isset($this->layersByClassMap)) {
            $this->layersByClassMap =
                it($this->layersIterable)
                ->reindex(function (LayerInterface $layer) { return \get_class($layer); })
                ->toArrayWithKeys();
        }

        return $this->layersByClassMap[$class];
    }
}
