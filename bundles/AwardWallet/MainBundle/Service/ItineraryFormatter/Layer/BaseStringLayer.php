<?php

namespace AwardWallet\MainBundle\Service\ItineraryFormatter\Layer;

use AwardWallet\MainBundle\Entity\Fee;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Vehicle;
use AwardWallet\MainBundle\FrameworkExtension\Translator\Trans;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\CallableEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\DashOnEmptyEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\EncoderInterface;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\BooleanTranslatorEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\DateTimeEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\Factory\ListImplodingEncoderFactory;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\IntegerEncoder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\Encoder\PropertiesArrayImploder;
use AwardWallet\MainBundle\Service\ItineraryFormatter\PropertiesList;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class BaseStringLayer implements DILayerInterface
{
    private ListImplodingEncoderFactory $listImplodingEncoderFactory;

    private DateTimeEncoderFactory $dateTimeEncoderFactory;

    private IntegerEncoder $integerEncoder;

    private DashOnEmptyEncoder $dashOnEmptyEncoder;

    private BooleanTranslatorEncoderFactory $booleanTranslatorEncoderFactory;
    private TranslatorInterface $translator;
    private PropertiesArrayImploder $propertiesArrayImploder;

    public function __construct(
        ListImplodingEncoderFactory $listImplodingEncoderFactory,
        DateTimeEncoderFactory $dateTimeEncoderFactory,
        IntegerEncoder $integerEncoder,
        DashOnEmptyEncoder $dashOnEmptyEncoder,
        BooleanTranslatorEncoderFactory $booleanTranslatorEncoderFactory,
        TranslatorInterface $translator,
        PropertiesArrayImploder $propertiesArrayImploder
    ) {
        $this->listImplodingEncoderFactory = $listImplodingEncoderFactory;
        $this->dateTimeEncoderFactory = $dateTimeEncoderFactory;
        $this->integerEncoder = $integerEncoder;
        $this->dashOnEmptyEncoder = $dashOnEmptyEncoder;
        $this->booleanTranslatorEncoderFactory = $booleanTranslatorEncoderFactory;
        $this->translator = $translator;
        $this->propertiesArrayImploder = $propertiesArrayImploder;
    }

    public function getEncodersMap(array $previousEncodersMap = []): array
    {
        $toEncoder = function (callable $callable): EncoderInterface {
            return new CallableEncoder($callable);
        };

        $commasImploder = $this->listImplodingEncoderFactory->make(', ');
        $dateNoTimeEncoder = $this->dateTimeEncoderFactory->make('long', 'none');
        $dateWithTimeEncoder = $this->dateTimeEncoderFactory->make('long', 'short');
        $yesNoEncoder = $this->booleanTranslatorEncoderFactory->make(
            new Trans('button.yes'),
            new Trans('button.no')
        );

        $stringEncodersMap = [
            // misc
            PropertiesList::AIRLINE_NAME => $previousEncodersMap[PropertiesList::AIRLINE_NAME]
                ->andThenIfExists($toEncoder(function ($input) {
                    if ($input instanceof Provider) {
                        return $input->getDisplayname();
                    }

                    return (string) $input;
                })),

            PropertiesList::CONFIRMATION_NUMBER => $previousEncodersMap[PropertiesList::CONFIRMATION_NUMBER]
                ->andThenIfExists($this->dashOnEmptyEncoder),

            // lists
            PropertiesList::TICKET_NUMBERS => $previousEncodersMap[PropertiesList::TICKET_NUMBERS]
                ->andThenIfExists($commasImploder),

            PropertiesList::FEES => $previousEncodersMap[PropertiesList::FEES_LIST]
                ->andThenIfExists($toEncoder(function (/** @var Fee[] $input */ $input) {
                    $sum =
                        it($input)
                        ->map(function (Fee $fee) { return $fee->getCharge(); })
                        ->sum();

                    return $sum ?: null;
                })),

            PropertiesList::SEATS => $previousEncodersMap[PropertiesList::SEATS]
                ->andThenIfExists($commasImploder),

            PropertiesList::CONFIRMATION_NUMBERS => $previousEncodersMap[PropertiesList::CONFIRMATION_NUMBERS]
                ->andThenIfExists($commasImploder),

            PropertiesList::TRAVEL_AGENCY_ACCOUNT_NUMBERS => $previousEncodersMap[PropertiesList::TRAVEL_AGENCY_ACCOUNT_NUMBERS]
                ->andThenIfExists($commasImploder),

            PropertiesList::ACCOUNT_NUMBERS => $previousEncodersMap[PropertiesList::ACCOUNT_NUMBERS]
                ->andThenIfExists($commasImploder),

            PropertiesList::TRAVELER_NAMES => $previousEncodersMap[PropertiesList::TRAVELER_NAMES]
                ->andThenIfExists($commasImploder),

            PropertiesList::ACCOMMODATIONS => $previousEncodersMap[PropertiesList::ACCOMMODATIONS]
                ->andThenIfExists($commasImploder),

            PropertiesList::VEHICLES => $previousEncodersMap[PropertiesList::VEHICLES]
                ->andThenIfExists($toEncoder(function (/** @var Vehicle[] $input */ $input) {
                    return array_map(function ($vehicle) {
                        $list = [];

                        foreach (Vehicle::getPropertyMessagesArray() as $property => $message) {
                            $method = 'get' . ucfirst($property);

                            if (!method_exists($vehicle, $method) || StringUtils::isEmpty($vehicle->$method())) {
                                continue;
                            }

                            $list[] = ($property !== 'model') ?
                                $this->translator->trans($message, [], Vehicle::TRANSLATION_DOMAIN) . ': ' . $vehicle->$method() :
                                $vehicle->$method();
                        }

                        return implode(', ', $list);
                    }, $input);
                }))
                ->andThenIfExists($commasImploder),

            PropertiesList::DISCOUNT_DETAILS => $previousEncodersMap[PropertiesList::DISCOUNT_DETAILS]
                ->andThenIfExists($this->propertiesArrayImploder)
                ->andThenIfExists($commasImploder),

            PropertiesList::PRICED_EQUIPMENT => $previousEncodersMap[PropertiesList::PRICED_EQUIPMENT]
                ->andThenIfExists($this->propertiesArrayImploder)
                ->andThenIfExists($commasImploder),

            PropertiesList::FARE_BASIS => $previousEncodersMap[PropertiesList::FARE_BASIS]
                ->andThenIfExists($commasImploder),

            PropertiesList::NON_REFUNDABLE => $previousEncodersMap[PropertiesList::NON_REFUNDABLE]
                ->andThenIfExists($toEncoder(function ($input) {
                    return $input ? $this->translator->trans(/** @Desc("No") */ 'reservation.non-refundable') : null;
                })),

            // room rates
            PropertiesList::ROOM_RATE => $previousEncodersMap[PropertiesList::ROOM_RATE]
                ->andThenIfExists($commasImploder),

            PropertiesList::ROOM_RATE_DESCRIPTION => $previousEncodersMap[PropertiesList::ROOM_RATE_DESCRIPTION]
                ->andThenIfExists($commasImploder),

            PropertiesList::ROOM_SHORT_DESCRIPTION => $previousEncodersMap[PropertiesList::ROOM_SHORT_DESCRIPTION]
                ->andThenIfExists($commasImploder),

            PropertiesList::ROOM_LONG_DESCRIPTION => $previousEncodersMap[PropertiesList::ROOM_LONG_DESCRIPTION]
                ->andThenIfExists($commasImploder),

            // dates
            PropertiesList::RESERVATION_DATE => $previousEncodersMap[PropertiesList::RESERVATION_DATE]
                ->andThenIfExists($dateNoTimeEncoder),

            PropertiesList::DEPARTURE_DATE => $previousEncodersMap[PropertiesList::DEPARTURE_DATE]
                ->andThenIfExists($dateWithTimeEncoder),

            PropertiesList::ARRIVAL_DATE => $previousEncodersMap[PropertiesList::ARRIVAL_DATE]
                ->andThenIfExists($dateWithTimeEncoder),

            PropertiesList::PICK_UP_DATE => $previousEncodersMap[PropertiesList::PICK_UP_DATE]
                ->andThenIfExists($dateWithTimeEncoder),

            PropertiesList::DROP_OFF_DATE => $previousEncodersMap[PropertiesList::DROP_OFF_DATE]
                ->andThenIfExists($dateWithTimeEncoder),

            PropertiesList::START_DATE => $previousEncodersMap[PropertiesList::START_DATE]
                ->andThenIfExists($dateWithTimeEncoder),

            PropertiesList::END_DATE => $previousEncodersMap[PropertiesList::END_DATE]
                ->andThenIfExists($dateWithTimeEncoder),

            PropertiesList::CHECK_IN_DATE => $previousEncodersMap[PropertiesList::CHECK_IN_DATE]
                ->andThenIfExists($dateWithTimeEncoder),

            PropertiesList::CHECK_OUT_DATE => $previousEncodersMap[PropertiesList::CHECK_OUT_DATE]
                ->andThenIfExists($dateWithTimeEncoder),

            PropertiesList::CANCELLATION_DEADLINE => $previousEncodersMap[PropertiesList::CANCELLATION_DEADLINE]
                ->andThenIfExists($dateWithTimeEncoder),

            // numbers
            PropertiesList::STOPS_COUNT => $previousEncodersMap[PropertiesList::STOPS_COUNT]
                ->andThenIfExists($this->integerEncoder),

            PropertiesList::GUEST_COUNT => $previousEncodersMap[PropertiesList::GUEST_COUNT]
                ->andThenIfExists($this->integerEncoder),

            PropertiesList::ADULTS_COUNT => $previousEncodersMap[PropertiesList::ADULTS_COUNT]
                ->andThenIfExists($this->integerEncoder),

            PropertiesList::KIDS_COUNT => $previousEncodersMap[PropertiesList::KIDS_COUNT]
                ->andThenIfExists($this->integerEncoder),

            PropertiesList::ROOM_COUNT => $previousEncodersMap[PropertiesList::ROOM_COUNT]
                ->andThenIfExists($this->integerEncoder),

            PropertiesList::IS_SMOKING => $previousEncodersMap[PropertiesList::IS_SMOKING]
                ->andThenIfNotEmpty($yesNoEncoder),

            PropertiesList::FREE_NIGHTS => $previousEncodersMap[PropertiesList::FREE_NIGHTS]
                ->andThenIfExists($this->integerEncoder),
        ];

        return \array_merge(
            $previousEncodersMap,
            $stringEncodersMap
        );
    }
}
