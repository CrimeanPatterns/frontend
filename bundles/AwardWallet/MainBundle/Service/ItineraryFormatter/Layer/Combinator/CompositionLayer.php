<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Combinator;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\LayerInterface;

/**
 * @NoDI()
 */
class CompositionLayer implements LayerInterface
{
    /**
     * @var LayerInterface[]
     */
    private $layers = [];

    public function __construct(LayerInterface ...$layers)
    {
        $this->layers = $layers;
    }

    public function getEncodersMap(array $previousEncodersMap = []): array
    {
        $result = [];

        foreach ($this->layers as $layer) {
            $result = $layer->getEncodersMap($result);
        }

        return $result;
    }
}
