<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;

trait LayerUpdateTrait
{
    /**
     * @param EncoderInterface[] $inputMap
     */
    protected function getLayerUpdater(array &$inputMap): callable
    {
        return function (string $code, callable $updater) use (&$inputMap) {
            if (isset($inputMap[$code])) {
                $inputMap[$code] = $updater($inputMap[$code]);
            }
        };
    }
}
