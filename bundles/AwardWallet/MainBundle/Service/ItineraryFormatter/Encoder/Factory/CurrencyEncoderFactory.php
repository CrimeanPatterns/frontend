<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;

class CurrencyEncoderFactory
{
    private DecimalEncoderFactory $decimalEncoderFactory;

    private LocalizeService $localizeService;

    public function __construct(
        DecimalEncoderFactory $decimalEncoderFactory,
        LocalizeService $localizeService
    ) {
        $this->decimalEncoderFactory = $decimalEncoderFactory;
        $this->localizeService = $localizeService;
    }

    public function make(string $currencyLayer, ?string $fallbackLayer = null): EncoderInterface
    {
        $decimalFormatter = $this->decimalEncoderFactory->make(2);

        return new CallableEncoder(function ($value, EncoderContext $encoderContext) use ($currencyLayer, $decimalFormatter, $fallbackLayer) {
            if (\preg_match('/^\d+([\,\. ]\d+(.\d+)?)?$/ims', $value)) {
                $currency = $encoderContext->getProperty(PropertiesList::CURRENCY, $currencyLayer);

                if (!isset($currency) && isset($fallbackLayer)) {
                    $currency = $encoderContext->getProperty(PropertiesList::CURRENCY, $fallbackLayer);
                }

                if (isset($currency)) {
                    return $this->formatCurrency($value, $currency, $encoderContext->locale);
                } else {
                    return $decimalFormatter->encode($value, $encoderContext);
                }
            }

            return $value;
        });
    }

    private function formatCurrency($value, $currency, $locale)
    {
        $value = filterBalance($value, true);

        return $this->localizeService->formatCurrency($value, $currency, 2, $locale);
    }
}
