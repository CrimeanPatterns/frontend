<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\EncoderContext;

class DateTimeEncoderFactory
{
    private LocalizeService $localizer;

    public function __construct(LocalizeService $localizer)
    {
        $this->localizer = $localizer;
    }

    public function make(string $dateType, string $timeType): EncoderInterface
    {
        return new CallableEncoder(function ($input, EncoderContext $encoderContext) use ($dateType, $timeType) {
            if (!($input instanceof \DateTime)) {
                $prevValue = $input;

                if (is_numeric($input)) {
                    $value = date_create("@" . $input);
                } else {
                    $value = date_create($input);
                }

                if (!$value) {
                    return $prevValue;
                }
            } else {
                $value = $input;
            }

            return $this->localizer->formatDateTime($value, $dateType, $timeType, $encoderContext->locale);
        });
    }
}
