<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Alliance;
use AwardWallet\MainBundle\Service\Lounge\DTO\LocationDTO;
use AwardWallet\MainBundle\Service\Lounge\DTO\ValueDTO;
use AwardWallet\MainBundle\Service\Lounge\Logger;
use AwardWallet\MainBundle\Service\Lounge\LoungeHelper;
use AwardWallet\Tests\Unit\BaseContainerTest;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @group frontend-unit
 */
class LoungeHelperTest extends BaseContainerTest
{
    private ?LoungeHelper $loungeHelper;

    public function _before()
    {
        parent::_before();

        $this->loungeHelper = new LoungeHelper(
            $this->em->getConnection(),
            $this->em,
            $this->makeEmpty(Logger::class)
        );
    }

    public function _after()
    {
        $this->loungeHelper = null;

        parent::_after();
    }

    /**
     * @dataProvider parseLocationDataProvider
     */
    public function testParseLocation(array $terminalLocations, array $gateLocations, LocationDTO $expected)
    {
        $this->assertEquals($expected, $this->loungeHelper->parseLocation($terminalLocations, $gateLocations));
    }

    public function parseLocationDataProvider(): array
    {
        return [
            'terminal + gate' => [
                [new ValueDTO('Airside - Terminal 2A')],
                [new ValueDTO('Airside. North Pier - after clearing Immigration and X-Ray machines proceed past the Duty Free Shop then turn left towards Gate 210. The lounge is located opposite Gate 208 and next to WHSmith Bookshop and Pharmacy 1. International flights only.')],
                LocationDTO::make('2A', '208', '210'),
            ],
            'terminal + gate, 2' => [
                [new ValueDTO('International Terminal - Terminal E')],
                [new ValueDTO('Airside - after TSA Security Checks, the lounge is located between Gates 2 and 3. International flights only.')],
                LocationDTO::make('E', '2', '3'),
            ],
            'terminal + gate, 3' => [
                [new ValueDTO('3a Terminal')],
                [new ValueDTO('Airside - after TSA Security Checks, the lounge is located between Gate 2 and Gate  3. International flights only.')],
                LocationDTO::make('3a', '2', '3'),
            ],
            'terminal + gate, 4' => [
                [new ValueDTO('VIP Terminal')],
                [new ValueDTO('Airside - after Security, next to Gates 9 and 8. International flights only.')],
                LocationDTO::make('VIP', '8', '9'),
            ],
            'terminal + gate, 5' => [
                [new ValueDTO('Concourse B')],
                [new ValueDTO('International departure, in front of gates 34-35, third floor.')],
                LocationDTO::make('B', '34', '35'),
            ],
            'terminal + gate, 6' => [
                [new ValueDTO('International B Concourse')],
                [new ValueDTO('Airside - the lounge is located above Boarding Gates A4 and A5. ')],
                LocationDTO::make('B', 'A4', 'A5'),
            ],
            'only gate' => [
                [new ValueDTO(null)],
                [new ValueDTO('Airside - near the Air France Boarding Area by Gate 1. ')],
                LocationDTO::make(null, '1', null),
            ],
            'multiple gates' => [
                [new ValueDTO(null)],
                [
                    new ValueDTO('Airside - near the Air France Boarding Area by Gates 1 and 2. '),
                    new ValueDTO('Gate 2.', false),
                ],
                LocationDTO::make(null, '2', null),
            ],
            'multiple gates, 2' => [
                [new ValueDTO(null)],
                [
                    new ValueDTO('Airside - near the Air France Boarding Area by Gates 1 and 2. '),
                    new ValueDTO('Gate 2.', false),
                    new ValueDTO('Gate 3.', false),
                ],
                LocationDTO::make(null, '2', '3'),
            ],
            'multiple gates, 3' => [
                [new ValueDTO(null)],
                [
                    new ValueDTO('Airside - near the Air France Boarding Area by Gates 1 and 2. '),
                    new ValueDTO('Gate 2.', false),
                    new ValueDTO('Gate A3.', false),
                ],
                LocationDTO::make('A', '2', 'A3'),
            ],
            'multiple gates, 4' => [
                [new ValueDTO(null)],
                [
                    new ValueDTO('Airside - near the Air France Boarding Area by Gates 1 and 2. '),
                    new ValueDTO('Gate 2.', false),
                    new ValueDTO('Gate A-3.', false),
                ],
                LocationDTO::make('A', '2', 'A3'),
            ],
            'multiple gates, 5' => [
                [new ValueDTO(null)],
                [
                    new ValueDTO('Airside - near the Air France Boarding Area by Gates 1 and 2. '),
                    new ValueDTO('Gate 2.'),
                ],
                LocationDTO::make(null, '1', '2'),
            ],
            'terminal prefix' => [
                [new ValueDTO('T2')],
                [],
                LocationDTO::make(2),
            ],
            'terminal prefix, 2' => [
                [new ValueDTO('t4')],
                [
                    new ValueDTO('Gate 2.'),
                ],
                LocationDTO::make(4, 2),
            ],
            'terminal prefix, 3' => [
                [new ValueDTO('t4 International')],
                [],
                LocationDTO::make(),
            ],
            'gate suffix' => [
                [],
                [
                    new ValueDTO('Adjacent to Gate 71A'),
                ],
                LocationDTO::make(null, '71A'),
            ],
            'text terminal' => [
                [new ValueDTO('Northeast Pier Terminal')],
                [],
                LocationDTO::make('Northeast Pier'),
            ],
            'the terminal' => [
                [new ValueDTO('Upper level, take the escalator located in the middle of the terminal. After reaching the upper level, pass the security filter and go to your right.')],
                [],
                LocationDTO::make(),
            ],
            'terminal by' => [
                [new ValueDTO('Satellite Terminal by Starbucks')],
                [],
                LocationDTO::make('Satellite'),
            ],
            'terminal in' => [
                [new ValueDTO('Airside – 10m right hand side of Level 6 from center of the terminal in front of Gate 15. * For departing flights only.')],
                [],
                LocationDTO::make(),
            ],
            'terminal across' => [
                [new ValueDTO('Main Concourse Across from Gate F1.')],
                [],
                LocationDTO::make('Main'),
            ],
            'boarding terminal' => [
                [new ValueDTO('Airside – Boarding concourse above the duty free area')],
                [],
                LocationDTO::make(),
            ],
            'in front of terminal' => [
                [new ValueDTO('In front of Terminal')],
                [],
                LocationDTO::make(),
            ],
            'multiple terminals' => [
                [new ValueDTO('South terminal. In the H and J connector (left of H concourse security)')],
                [],
                LocationDTO::make('South'),
            ],
            'internationsl terminal' => [
                [new ValueDTO('International Terminal')],
                [],
                LocationDTO::make(),
            ],
            'non terminal' => [
                [new ValueDTO('Main Terminal - Non Schengen')],
                [],
                LocationDTO::make('Main'),
            ],
            'before terminal' => [
                [new ValueDTO('Inside Airport VIP lounge at the Domestic Terminal before security, 2nd floor near check-in. Delta One or Prestige Class or First Class on Korean Air and SkyTeam Elite Plus members are eligible to access to the lounge.')],
                [],
                LocationDTO::make('Domestic'),
            ],
        ];
    }

    /**
     * @dataProvider parseCarrierDataProvider
     */
    public function testParseCarrier(array $airlines, array $alliances, array $expectedAirlines, array $expectedAlliances)
    {
        $result = $this->loungeHelper->parseCarrier($airlines, $alliances);

        $actualAirlines = it($result->getAirlines())
            ->map(fn (Airline $airline) => $airline->getFsCode())
            ->sort()
            ->toArray();
        $this->assertEquals($expectedAirlines, $actualAirlines);

        $actualAlliances = it($result->getAlliances())
            ->map(fn (Alliance $alliance) => $alliance->getName())
            ->sort()
            ->toArray();
        $this->assertEquals($expectedAlliances, $actualAlliances);
    }

    public function parseCarrierDataProvider(): array
    {
        return [
            'delta' => [
                [new ValueDTO('Delta')], [],
                ['DL'], ['SkyTeam'],
            ],
            'klm' => [
                [new ValueDTO('Delta'), new ValueDTO('klm')], [new ValueDTO('test')],
                ['DL', 'KL'], ['SkyTeam'],
            ],
            'finnair' => [
                [new ValueDTO('yyyy'), new ValueDTO('Finnair')], [new ValueDTO('test')],
                ['AY'], ['Oneworld'],
            ],
        ];
    }
}
