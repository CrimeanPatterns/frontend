<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\DTO;

class ParserSelectorResponse
{
    private array $parsers = [];

    /**
     * @param string[] $parsers
     */
    public function addRoute(
        string $from,
        string $to,
        \DateTime $depDate,
        string $flightClass,
        int $passengersCount,
        array $parsers,
        bool $fullSearch
    ): void {
        $this->parsers[$from][$to][$depDate->format('Y-m-d')][$flightClass][$passengersCount] = [
            'parsers' => $parsers,
            'fullSearch' => $fullSearch,
        ];
    }

    /**
     * @return string[]
     */
    public function getParsers(
        string $from,
        string $to,
        \DateTime $depDate,
        string $flightClass,
        int $passengersCount
    ): array {
        return $this->parsers[$from][$to][$depDate->format('Y-m-d')][$flightClass][$passengersCount]['parsers'] ?? [];
    }

    public function isFullSearch(
        string $from,
        string $to,
        \DateTime $depDate,
        string $flightClass,
        int $passengersCount
    ): bool {
        return $this->parsers[$from][$to][$depDate->format('Y-m-d')][$flightClass][$passengersCount]['fullSearch'] ?? false;
    }

    public function getAllParsers(): array
    {
        $selectedParsers = [];

        foreach ($this->parsers as $fromAirport => $to) {
            foreach ($to as $toAirport => $depDates) {
                foreach ($depDates as $depDate => $flightClasses) {
                    foreach ($flightClasses as $flightClass => $passengersCount) {
                        foreach ($passengersCount as $passengers => $data) {
                            $selectedParsers[] = [
                                'from' => $fromAirport,
                                'to' => $toAirport,
                                'depDate' => $depDate,
                                'flightClass' => $flightClass,
                                'passengers' => $passengers,
                                'parsers' => $data['parsers'],
                                'fullSearch' => $data['fullSearch'],
                            ];
                        }
                    }
                }
            }
        }

        return $selectedParsers;
    }
}
