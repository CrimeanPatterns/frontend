<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\ListImplodingEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\ListMapperEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\MaskedPostfixEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;

class MailCurrentValuesLayer implements DILayerInterface
{
    use LayerUpdateTrait;
    use CachedLayerTrait;

    private CurrentBaseStringValuesLayer $currentBaseStringValuesLayer;

    private CurrentValuesLayer $baseEncodersLayer;

    private MaskedPostfixEncoderFactory $maskedPostfixEncoderFactory;

    private ListMapperEncoderFactory $listMapperEncoderFactory;

    private ListImplodingEncoderFactory $listImplodingEncoderFactory;

    public function __construct(
        CurrentBaseStringValuesLayer $currentBaseStringValuesLayer,
        CurrentValuesLayer $baseEncodersLayer,
        MaskedPostfixEncoderFactory $maskedPostfixEncoderFactory,
        ListMapperEncoderFactory $listMapperEncoderFactory,
        ListImplodingEncoderFactory $listImplodingEncoderFactory
    ) {
        $this->currentBaseStringValuesLayer = $currentBaseStringValuesLayer;
        $this->baseEncodersLayer = $baseEncodersLayer;
        $this->maskedPostfixEncoderFactory = $maskedPostfixEncoderFactory;
        $this->listMapperEncoderFactory = $listMapperEncoderFactory;
        $this->listImplodingEncoderFactory = $listImplodingEncoderFactory;
    }

    protected function doGetEncodersMap(array $previousEncodersMap = []): array
    {
        $inputMap = $this->currentBaseStringValuesLayer->getEncodersMap();
        $baseMap = $this->baseEncodersLayer->getEncodersMap();
        $inputUpdater = $this->getLayerUpdater($inputMap);

        $inputUpdater(PropertiesList::DEPARTURE_NAME, function (EncoderInterface $encoder) {
            return $encoder->andThenIfNotEmpty($this->makeCodeFormatter(PropertiesList::DEPARTURE_AIRPORT_CODE));
        });
        $inputUpdater(PropertiesList::ARRIVAL_NAME, function (EncoderInterface $encoder) {
            return $encoder->andThenIfNotEmpty($this->makeCodeFormatter(PropertiesList::ARRIVAL_AIRPORT_CODE));
        });
        $inputMap[PropertiesList::AIRLINE_NAME] =
            $baseMap[PropertiesList::AIRLINE_NAME]->andThenIfNotEmpty(new CallableEncoder(function ($input) {
                if (\is_string($input)) {
                    return $input;
                } elseif ($input instanceof Provider) {
                    if (($input->getKind() == PROVIDER_KIND_AIRLINE) && $input->getIATACode()) {
                        return sprintf("%s (%s)", $input->getName(), $input->getIATACode());
                    } else {
                        return $input->getName();
                    }
                }

                return $input;
            }));

        $inputMap[PropertiesList::ACCOUNT_NUMBERS] =
            $baseMap[PropertiesList::ACCOUNT_NUMBERS]
                ->andThenIfNotEmpty(
                    $this->listMapperEncoderFactory->make(
                        $this->maskedPostfixEncoderFactory->make('X', 4)
                    )
                )
                ->andThenIfNotEmpty($this->listImplodingEncoderFactory->make(', '));

        return $inputMap;
    }

    private function makeCodeFormatter(string $code): CallableEncoder
    {
        return new CallableEncoder(function (string $input, EncoderContext $encoderContext) use ($code) {
            $depCode = $encoderContext->getProperty(
                $code,
                CurrentValuesLayer::class
            );

            if (empty($depCode)) {
                return $input;
            }

            return \sprintf("%s (%s)", $input, $depCode);
        });
    }
}
