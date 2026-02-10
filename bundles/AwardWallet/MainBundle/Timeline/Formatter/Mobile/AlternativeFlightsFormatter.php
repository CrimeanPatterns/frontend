<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Entity\MileValue;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\MileValue\FoundPrices;
use AwardWallet\MainBundle\Service\MileValue\MileValueAlternativeFlights;
use AwardWallet\MainBundle\Service\MileValue\MileValueAlternativeFlightsItem;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights\Block;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights\Choice;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights\Code;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights\Dates;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\AlternativeFlights\Layover;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AlternativeFlightsFormatter
{
    private MileValueAlternativeFlights $mileValueAlternativeFlights;
    private TranslatorInterface $translator;

    public function __construct(
        MileValueAlternativeFlights $mileValueAlternativeFlights,
        TranslatorInterface $translator
    ) {
        $this->mileValueAlternativeFlights = $mileValueAlternativeFlights;
        $this->translator = $translator;
    }

    public function format(Tripsegment $tripsegment): array
    {
        $mileValue = $this->loadMileValue($tripsegment);

        if (!$mileValue) {
            return [];
        }

        $alternativeFlights = $this->mileValueAlternativeFlights->getTimelineFields($mileValue);

        if (!$alternativeFlights) {
            return [];
        }

        return $this->doFormat($alternativeFlights);
    }

    protected function doFormat(array $alternativeFlights): array
    {
        [
            'alternativeFlights' => [
                'flights' => $flights,
            ]
        ] = $alternativeFlights;
        $selected = (int) $alternativeFlights['alternativeFlights']['customPick'];

        return [
            'choices' =>
                it($flights)
                ->map(fn (array $flight) => $this->formatFlightChoice($flight, $alternativeFlights['alternativeFlights']['travelersCount']))
                ->append(new Choice(
                    MileValue::CUSTOM_PICK_USER_INPUT,
                    Choice::TYPE_CUSTOM,
                    null,
                    $selected === MileValue::CUSTOM_PICK_USER_INPUT ?
                        $alternativeFlights['alternativeFlights']['customAlternativeCost'] :
                        null
                ))
                ->toArray(),
            'selected' => $selected,
        ];
    }

    protected function formatFlightChoice(array $flight, string $passengers): Choice
    {
        $choice = new Choice(
            $flight['type'] === FoundPrices::CHEAPEST_KEY ?
                MileValue::CUSTOM_PICK_CHEAPEST :
                MileValue::CUSTOM_PICK_YOUR_AWARD,
            $flight['type'],
            $flight['airline'],
            $flight['price'],
        );

        if (StringUtils::isNotEmpty($flight['operating'])) {
            $blocks[] = Block::fromKindNameValue(
                Block::KIND_STRING,
                $this->translator->trans('itineraries.trip.air.operator', [], 'trips'),
                $flight['operating']
            );
        }

        $blocks[] = Block::fromKindNameValue(
            Block::KIND_STRING,
            $this->translator->trans('itineraries.trip.cabin', [], 'trips'),
            $flight['service'],
        );
        $blocks[] = Block::fromKindNameValue(
            Block::KIND_STRING,
            $this->translator->trans('itineraries.trip.passengers', [], 'trips'),
            $passengers,
        );

        foreach ($flight['routes'] as $route) {
            $blocks[] = new Dates(
                $route['date'],
                $route['day'],
                new Code(
                    $route['depCode'],
                    $route['depTime']
                ),
                new Code(
                    $route['arrCode'],
                    $route['arrTime']
                )
            );

            foreach ($route['timing']['lo'] as $layover) {
                $blocks[] = new Layover($layover['code'], $layover['duration']);
            }

            $blocks[] = Block::fromKindNameValue(
                Block::KIND_STRING,
                $this->translator->trans('itineraries.trip.duration', [], 'trips'),
                $route['timing']['totalDuration']
            );
        }

        $choice->blocks = $blocks;

        return $choice;
    }

    protected function loadMileValue(Tripsegment $tripsegment): ?MileValueAlternativeFlightsItem
    {
        $tripId = $tripsegment->getTripid()->getId();
        $mileValueMap = $this->mileValueAlternativeFlights->formatMileValueDataByTrips([$tripId]);

        if (!isset($mileValueMap[$tripId])) {
            return null;
        }

        return $mileValueMap[$tripId];
    }
}
