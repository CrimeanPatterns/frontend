<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;

interface LayerInterface
{
    /**
     * @param array<string, EncoderInterface> $previousEncodersMap key => EncoderInterface map
     * @return array<string, EncoderInterface> key => EncoderInterface map
     */
    public function getEncodersMap(array $previousEncodersMap = []): array;
}
