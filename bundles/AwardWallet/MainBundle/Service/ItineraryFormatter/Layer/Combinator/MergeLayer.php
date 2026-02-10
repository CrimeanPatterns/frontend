<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Combinator;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\LayerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iter\reverseList;

/**
 * @NoDI()
 */
class MergeLayer implements LayerInterface
{
    /**
     * @var LayerInterface[]
     */
    private $layers;

    public function __construct(LayerInterface ...$layers)
    {
        $this->layers = $layers;
    }

    public function getEncodersMap(array $previousEncodersMap = []): array
    {
        $result = [];

        /** @var LayerInterface $layer */
        foreach (reverseList($this->layers) as $layer) {
            $result = \array_merge($result, $layer->getEncodersMap($previousEncodersMap));
        }

        return $result;
    }
}
