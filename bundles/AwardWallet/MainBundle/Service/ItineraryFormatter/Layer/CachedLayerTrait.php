<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;

trait CachedLayerTrait
{
    /**
     * @var EncoderInterface[]
     */
    private array $cache;

    /**
     * @return EncoderInterface[]
     */
    public function getEncodersMap(array $previousEncodersMap = []): array
    {
        if (isset($this->cache)) {
            return $this->cache;
        }

        return $this->cache = $this->doGetEncodersMap($previousEncodersMap);
    }

    /**
     * @return EncoderInterface[]
     */
    abstract protected function doGetEncodersMap(array $previousEncodersMap = []): array;
}
