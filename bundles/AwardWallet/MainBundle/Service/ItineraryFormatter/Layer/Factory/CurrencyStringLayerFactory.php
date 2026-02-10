<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\Factory;

use AwardWallet\MainBundle\Entity\Fee;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\CurrencyEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\ListMapperEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\CallableLayer;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\LayerInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Layer\LayerUpdateTrait;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CurrencyStringLayerFactory
{
    use LayerUpdateTrait;

    private CurrencyEncoderFactory $currencyEncoderFactory;

    private ListMapperEncoderFactory $listMapperEncoderFactory;

    public function __construct(
        CurrencyEncoderFactory $currencyEncoderFactory,
        ListMapperEncoderFactory $listMapperEncoderFactory
    ) {
        $this->currencyEncoderFactory = $currencyEncoderFactory;
        $this->listMapperEncoderFactory = $listMapperEncoderFactory;
    }

    public function make(string $baseLayer, ?string $fallbackLayer = null): LayerInterface
    {
        $currencyEncoder = $this->currencyEncoderFactory->make($baseLayer, $fallbackLayer);

        return new CallableLayer(function (array $encodersMap) use ($currencyEncoder) {
            $layerUpdater = $this->getLayerUpdater($encodersMap);

            foreach (
                [
                    PropertiesList::COST,
                    PropertiesList::DISCOUNT,
                    PropertiesList::TOTAL_CHARGE,
                    PropertiesList::FEES,
                ] as $propertyCode
            ) {
                $layerUpdater($propertyCode, function (EncoderInterface $encoder) use ($currencyEncoder) {
                    return $encoder->andThenIfExists($currencyEncoder);
                });
            }

            $layerUpdater(PropertiesList::FEES_LIST, function (EncoderInterface $encoder) use ($currencyEncoder) {
                $currencyMapper = $this->listMapperEncoderFactory->make($currencyEncoder);

                return $encoder
                    ->andThenIfExists(new CallableEncoder(function (array $fees) {
                        return
                            it($fees)
                            ->map(function (Fee $fee) {
                                return $fee->getCharge();
                            })
                            ->toArrayWithKeys();
                    }))
                    ->andThenIfExists($currencyMapper);
            });

            return $encodersMap;
        });
    }
}
