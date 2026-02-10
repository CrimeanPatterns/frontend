<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\FrameworkExtension\Twig\AwTwigExtension;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\ListImplodingEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;

class DesktopLayer implements DILayerInterface
{
    private ListImplodingEncoderFactory $listImploder;

    private AwTwigExtension $awTwigExtension;

    public function __construct(
        AwTwigExtension $awTwigExtension,
        ListImplodingEncoderFactory $listImplodingEncoderFactory
    ) {
        $this->listImploder = $listImplodingEncoderFactory;
        $this->awTwigExtension = $awTwigExtension;
    }

    /**
     * @param EncoderInterface[] $previousEncodersMap
     */
    public function getEncodersMap(array $previousEncodersMap = []): array
    {
        $toEncoder = function (callable $callable): EncoderInterface {
            return new CallableEncoder($callable);
        };

        return [
            // Trip
            PropertiesList::IS_SMOKING => $previousEncodersMap[PropertiesList::IS_SMOKING]
                ->andThenIfExists($toEncoder(function (bool $input) {
                    return $input ? 'yes' : 'no';
                })),

            PropertiesList::FARE_BASIS => $previousEncodersMap[PropertiesList::FARE_BASIS]
                ->andThenIfNotEmpty($this->listImploder->make('')),

            // Reservation
            PropertiesList::ROOM_LONG_DESCRIPTION => $previousEncodersMap[PropertiesList::ROOM_LONG_DESCRIPTION]
                ->andThenIfNotEmpty($this->listImploder->make('; ')),

            PropertiesList::ROOM_SHORT_DESCRIPTION => $previousEncodersMap[PropertiesList::ROOM_SHORT_DESCRIPTION]
                ->andThenIfNotEmpty($this->listImploder->make('; ')),

            PropertiesList::ROOM_RATE_DESCRIPTION => $previousEncodersMap[PropertiesList::ROOM_RATE_DESCRIPTION]
                ->andThenIfNotEmpty($this->listImploder->make('; ')),

            // common
            PropertiesList::NOTES => $previousEncodersMap[PropertiesList::NOTES]
                ->andThenIfNotEmpty($toEncoder(function ($input) {
                    return $this->awTwigExtension->auto_link(nl2br($input));
                })),
        ];
    }
}
