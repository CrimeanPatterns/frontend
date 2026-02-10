<?php

namespace AwardWallet\Tests\Unit\AwardWallet\MainBundle\Service\AirportTerminalMatcher;

use AwardWallet\MainBundle\Service\AirportTerminalMatcher\Matcher;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class MatcherTest extends BaseContainerTest
{
    private ?Matcher $matcher;

    public function _before()
    {
        parent::_before();

        $this->matcher = $this->container->get(Matcher::class);
    }

    public function _after()
    {
        $this->matcher = null;

        parent::_after();
    }

    /**
     * @dataProvider dataProvider
     */
    public function test(array $calls, array $aliases)
    {
        foreach ($aliases as $airportCode => $terminals) {
            $this->db->executeQuery("DELETE FROM AirportTerminal WHERE AirportCode = '$airportCode'");

            foreach ($terminals as $terminalName => $terminalAliases) {
                $terminalId = $this->db->haveInDatabase('AirportTerminal', [
                    'AirportCode' => $airportCode,
                    'Name' => $terminalName,
                ]);

                foreach ($terminalAliases as $alias) {
                    $this->db->haveInDatabase('AirportTerminalAlias', [
                        'AirportTerminalID' => $terminalId,
                        'Alias' => $alias,
                    ]);
                }
            }
        }

        foreach ($calls as $call) {
            $this->assertEquals($call[0], $this->matcher->match($call[1], $call[2]));
        }
    }

    public function dataProvider()
    {
        return [
            [
                [
                    $this->call('1', 'ABC', '1'),
                    $this->call('1', 'ABC', 'Terminal 1'),
                    $this->call('Terminal 2', 'ABC', 'Terminal 2'),
                ], array_merge_recursive(
                    $this->terminal('ABC', '1', ['Terminal 1'])
                ),
            ],

            [
                [
                    $this->call('Main', 'ABC', 'Main'),
                    $this->call('A', 'ABC', 'Main Terminal'),
                    $this->call('F', 'ABC', 'f term'),
                    $this->call('F', 'ABC', 'f'),
                    $this->call('A', 'ABC', 'main  terminal'),
                    $this->call('2F', 'CBA', '2-f'),
                ], array_merge_recursive(
                    $this->terminal('ABC', 'A', ['Terminal A', 'Main Terminal', 'A Term']),
                    $this->terminal('ABC', 'F', ['Terminal F', 'F Term']),
                    $this->terminal('ABC', 'C', ['Terminal C']),
                    $this->terminal('CBA', '2F', ['Terminal 2f', '2-F']),
                    $this->terminal('CBA', '5', ['5 terminal']),
                ),
            ],

            [
                [
                    $this->call('Terminal 1', 'ABC', 'Terminal 1'),
                ], array_merge_recursive(
                    $this->terminal('ABC', '1', ['Terminal 1']),
                    $this->terminal('ABC', '2', ['Terminal 1']),
                ),
            ],
        ];
    }

    private function terminal(string $airportCode, string $terminal, array $aliases = []): array
    {
        return [
            $airportCode => [
                $terminal => $aliases,
            ],
        ];
    }

    private function call(?string $expected, string $airportCode, string $rawTerminal): array
    {
        return [$expected, $airportCode, $rawTerminal];
    }
}
