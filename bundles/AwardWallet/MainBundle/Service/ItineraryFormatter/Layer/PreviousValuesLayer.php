<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\ListExplodingEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\TimestampToDateTimeEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\ItineraryWrapper;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertiesDB;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesDB\PropertyInfo;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class PreviousValuesLayer implements DILayerInterface
{
    use CachedLayerTrait;

    private const TIMESTAMP_TO_DATETIME = [
        PropertiesList::PICK_UP_DATE,
        PropertiesList::DROP_OFF_DATE,
        PropertiesList::CHECK_IN_DATE,
        PropertiesList::CHECK_OUT_DATE,
        PropertiesList::RESERVATION_DATE,
        PropertiesList::START_DATE,
        PropertiesList::END_DATE,
        PropertiesList::DEPARTURE_DATE,
        PropertiesList::ARRIVAL_DATE,
    ];

    private const VERTICAL_BAR_SEPARATED_LISTS = [
        PropertiesList::ROOM_RATE,
        PropertiesList::ROOM_RATE_DESCRIPTION,
        PropertiesList::ROOM_SHORT_DESCRIPTION,
        PropertiesList::ROOM_LONG_DESCRIPTION,
    ];

    private const COMMAS_SEPARATED_LISTS = [
        PropertiesList::TICKET_NUMBERS,
        PropertiesList::SEATS,
        PropertiesList::CONFIRMATION_NUMBERS,
        PropertiesList::TRAVEL_AGENCY_ACCOUNT_NUMBERS,
        PropertiesList::ACCOUNT_NUMBERS,
        PropertiesList::TRAVELER_NAMES,
    ];

    private PropertiesDB $propertiesDB;

    private TimestampToDateTimeEncoder $timestampToDateTimeEncoder;

    private ListExplodingEncoderFactory $listExplodingEncoderFactory;

    public function __construct(
        PropertiesDB $propertiesDB,
        TimestampToDateTimeEncoder $timestampToDateTimeEncoder,
        ListExplodingEncoderFactory $listExplodingEncoderFactory
    ) {
        $this->propertiesDB = $propertiesDB;
        $this->timestampToDateTimeEncoder = $timestampToDateTimeEncoder;
        $this->listExplodingEncoderFactory = $listExplodingEncoderFactory;
    }

    protected function doGetEncodersMap(array $previousEncodersMap = []): array
    {
        $changesExtractors =
            it($this->propertiesDB->getProperties())
            ->reindex(function (PropertyInfo $info) { return $info->getCode(); })
            ->map(function (PropertyInfo $info) {
                $propertyName = $info->getCode();

                return new CallableEncoder(function (ItineraryWrapper $changePack) use ($propertyName) {
                    return $changePack
                        ->getChanges()
                        ->getPreviousValue(
                            $propertyName,
                            $changePack->getMinChangeDate()
                        );
                });
            })
            ->toArrayWithKeys();

        $updateExtractor = function (string $code, callable $modifier) use (&$changesExtractors) {
            $changesExtractors[$code] = $modifier($changesExtractors[$code]);
        };

        foreach (self::TIMESTAMP_TO_DATETIME as $timestampProperty) {
            $updateExtractor(
                $timestampProperty,
                function (EncoderInterface $encoder) {
                    return $encoder->andThenIfExists($this->timestampToDateTimeEncoder);
                }
            );
        }

        $verticalBarExploder = $this->listExplodingEncoderFactory->make(' | ');

        foreach (self::VERTICAL_BAR_SEPARATED_LISTS as $propertyCode) {
            $updateExtractor(
                $propertyCode,
                function (EncoderInterface $encoder) use ($verticalBarExploder) {
                    return $encoder->andThenIfExists($verticalBarExploder);
                }
            );
        }

        $explodeTrimmer = new CallableEncoder(function ($input) {
            return it(\explode(',', $input))
                ->map('\\trim')
                ->toArray();
        });

        foreach (self::COMMAS_SEPARATED_LISTS as $propertyCode) {
            $updateExtractor($propertyCode, function (EncoderInterface $encoder) use ($explodeTrimmer) {
                return $encoder->andThenIfExists($explodeTrimmer);
            });
        }

        return $changesExtractors;
    }
}
