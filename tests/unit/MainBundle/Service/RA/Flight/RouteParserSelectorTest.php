<?php

namespace AwardWallet\Tests\Unit\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Service\RA\Flight\DTO\ParserSelectorRequest;
use AwardWallet\MainBundle\Service\RA\Flight\DTO\ParserSelectorResponse;
use AwardWallet\MainBundle\Service\RA\Flight\RouteParserSelector;
use AwardWallet\Tests\Modules\DbBuilder\Provider;
use AwardWallet\Tests\Modules\DbBuilder\RaFlightFullSearchStat;
use AwardWallet\Tests\Modules\DbBuilder\RAFlightRouteSearchVolume;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group frontend-unit
 */
class RouteParserSelectorTest extends BaseContainerTest
{
    private ?RouteParserSelector $routeParserSelector;

    public function _before()
    {
        parent::_before();

        $this->routeParserSelector = $this->container->get(RouteParserSelector::class);

        $this->db->executeQuery('DELETE FROM RaFlightFullSearchStat');
        $this->db->executeQuery('DELETE FROM RAFlightRouteSearchVolume');
    }

    public function _after()
    {
        $this->routeParserSelector = null;

        parent::_after();
    }

    /**
     * @dataProvider dataProvider
     * @param ParserSelectorRequest|ParserSelectorRequest[] $requests
     * @param string[] $availableParsers
     * @param RAFlightRouteSearchVolume[] $searchVolumes
     * @param RaFlightFullSearchStat[] $fullSearchStats
     */
    public function test(
        ParserSelectorResponse $expected,
        $requests,
        array $availableParsers,
        array $searchVolumes,
        array $fullSearchStats
    ): void {
        foreach ($searchVolumes as $searchVolume) {
            $this->dbBuilder->makeRAFlightRouteSearchVolume($searchVolume);
        }

        foreach ($fullSearchStats as $fullSearchStat) {
            $this->dbBuilder->makeRaFlightFullSearchStat($fullSearchStat);
        }

        $preparedAvailableParsers = [];

        foreach ($availableParsers as $parser) {
            $this->dbBuilder->makeProvider($provider = Provider::createWithCode($parser));
            $preparedAvailableParsers[$provider->getFields()['Code']] = $provider->getId();
        }

        if (!is_array($requests)) {
            $requests = [$requests];
        }

        $actual = $this->routeParserSelector->getRouteParsers($requests, $preparedAvailableParsers);
        $this->assertEquals($expected->getAllParsers(), $actual->getAllParsers());
    }

    public function dataProvider(): array
    {
        return [
            'full scan new route' => self::case(
                [
                    self::route('JFK', 'LAX', '2021-01-01', 'business', 1, ['foo', 'bar', 'baz'], true),
                ],
                self::request(['JFK-LAX'], ['2021-01-01'], ['business']),
            ),

            'full scan after 2 weeks since last full scan' => self::case(
                [
                    self::route('JFK', 'LAX', '2021-01-01', 'business', 1, ['foo', 'bar', 'baz'], true),
                ],
                self::request(['JFK-LAX'], ['2021-01-01'], ['business']),
                [],
                [
                    new RaFlightFullSearchStat('JFK', 'LAX', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-15 day')),
                ]
            ),

            'no full scan if 2 weeks not passed' => self::case(
                [
                    self::route('JFK', 'LAX', '2021-01-01', 'business', 1, []),
                ],
                self::request(['JFK-LAX'], ['2021-01-01'], ['business']),
                [],
                [
                    new RaFlightFullSearchStat('JFK', 'LAX', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-13 day')),
                ]
            ),

            'full scan if changed flight class' => self::case(
                [
                    self::route('JFK', 'LAX', '2021-01-01', 'economy', 1, ['foo', 'bar', 'baz'], true),
                ],
                self::request(['JFK-LAX'], ['2021-01-01'], ['economy']),
                [],
                [
                    new RaFlightFullSearchStat('JFK', 'LAX', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-13 day')),
                ]
            ),

            'full scan multiple routes' => self::case(
                [
                    self::route('JFK', 'SFO', '2021-01-01', 'business', 1, []),
                    self::route('JFK', 'NYC', '2021-01-01', 'business', 1, ['foo', 'bar', 'baz'], true),
                    self::route('LAX', 'SFO', '2021-01-01', 'business', 1, ['foo', 'bar', 'baz'], true),
                    self::route('LAX', 'NYC', '2021-01-01', 'business', 1, []),
                ],
                self::request(['JFK-SFO', 'JFK-NYC', 'LAX-SFO', 'LAX-NYC'], ['2021-01-01'], ['business']),
                [],
                [
                    new RaFlightFullSearchStat('JFK', 'SFO', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                    new RaFlightFullSearchStat('LAX', 'NYC', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                ]
            ),

            'scan specific route' => self::case(
                [
                    self::route('JFK', 'LAX', '2021-01-01', 'business', 1, ['foo', 'baz']),
                ],
                self::request(['JFK-LAX'], ['2021-01-01'], ['business']),
                [
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 1, 0)->setProvider(Provider::createWithCode('foo')),
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 0, 1)->setProvider(Provider::createWithCode('baz')),
                ],
                [
                    new RaFlightFullSearchStat('JFK', 'LAX', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                ]
            ),

            'scan specific route, 2' => self::case(
                [
                    self::route('JFK', 'LAX', '2021-01-01', 'business', 1, ['baz']),
                ],
                self::request(['JFK-LAX'], ['2021-01-01'], ['business']),
                [
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 0, 0)->setProvider(Provider::createWithCode('foo')),
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 0, 1)->setProvider(Provider::createWithCode('baz')),
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 0, 0)->setProvider(Provider::createWithCode('bar')),
                ],
                [
                    new RaFlightFullSearchStat('JFK', 'LAX', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                ]
            ),

            'scan specific route, change date' => self::case(
                [
                    self::route('JFK', 'LAX', '2021-01-12', 'business', 1, ['foo', 'bar', 'baz'], true),
                ],
                self::request(['JFK-LAX'], ['2021-01-12'], ['business']),
                [
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 1, 0)->setProvider(Provider::createWithCode('foo')),
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 0, 1)->setProvider(Provider::createWithCode('baz')),
                ],
                [
                    new RaFlightFullSearchStat('JFK', 'LAX', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                ]
            ),

            'scan specific route, change travelers' => self::case(
                [
                    self::route('JFK', 'LAX', '2021-01-01', 'business', 2, ['foo', 'bar', 'baz'], true),
                ],
                self::request(['JFK-LAX'], ['2021-01-01'], ['business'], 2),
                [
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 1, 0)->setProvider(Provider::createWithCode('foo')),
                    RAFlightRouteSearchVolume::create('JFK', 'LAX', 0, 1)->setProvider(Provider::createWithCode('baz')),
                ],
                [
                    new RaFlightFullSearchStat('JFK', 'LAX', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                ]
            ),

            'complex' => self::case(
                [
                    self::route('JFK', 'NYC', '2021-01-01', 'business', 1, ['foo', 'baz']),
                    self::route('JFK', 'SFO', '2021-01-01', 'business', 1, ['foo', 'bar', 'baz'], true),
                    self::route('LAX', 'NYC', '2021-01-01', 'business', 1, []),
                    self::route('LAX', 'SFO', '2021-01-01', 'business', 1, ['baz']),
                ],
                self::request(['JFK-NYC', 'JFK-SFO', 'LAX-NYC', 'LAX-SFO'], ['2021-01-01'], ['business']),
                [
                    RAFlightRouteSearchVolume::create('JFK', 'NYC', 0, 1)->setProvider(Provider::createWithCode('baz')),
                    RAFlightRouteSearchVolume::create('JFK', 'NYC', 67, 23)->setProvider(Provider::createWithCode('foo')),
                    RAFlightRouteSearchVolume::create('JFK', 'NYC', 0, 0)->setProvider(Provider::createWithCode('bar')),
                    RAFlightRouteSearchVolume::create('LAX', 'NYC', 0, 0)->setProvider(Provider::createWithCode('baz')),
                    RAFlightRouteSearchVolume::create('LAX', 'SFO', 0, 991)->setProvider(Provider::createWithCode('baz')),
                ],
                [
                    new RaFlightFullSearchStat('JFK', 'NYC', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                    new RaFlightFullSearchStat('JFK', 'SFO', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-19 day')),
                    new RaFlightFullSearchStat('LAX', 'NYC', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                    new RaFlightFullSearchStat('LAX', 'SFO', self::period('2021-01-01'), 'business', 1, date_create('-1 day'), date_create('-1 day')),
                ]
            ),

            '2 queries' => self::case(
                [
                    self::route('JFK', 'NYC', '2021-01-01', 'business', 1, ['foo', 'bar', 'baz'], true),
                    self::route('LAX', 'NYC', '2021-01-01', 'business', 1, ['foo', 'bar', 'baz'], true),
                    self::route('DME', 'PEE', '2021-02-01', 'economy', 2, ['foo', 'bar', 'baz'], true),
                ],
                [
                    self::request(['JFK-NYC', 'LAX-NYC'], ['2021-01-01'], ['business']),
                    self::request(['DME-PEE'], ['2021-02-01'], ['economy'], 2),
                ]
            ),
        ];
    }

    /**
     * @param ParserSelectorRequest|ParserSelectorRequest[] $requests
     * @param RAFlightRouteSearchVolume[] $searchVolumes
     * @param RaFlightFullSearchStat[] $fullSearchStats
     * @param string[] $availableParsers
     */
    private static function case(
        array $expected,
        $requests,
        array $searchVolumes = [],
        array $fullSearchStats = [],
        array $availableParsers = ['foo', 'bar', 'baz']
    ): array {
        return [
            self::response($expected),
            $requests,
            $availableParsers,
            $searchVolumes,
            $fullSearchStats,
        ];
    }

    private static function response(array $routes): ParserSelectorResponse
    {
        $response = new ParserSelectorResponse();

        foreach ($routes as $route) {
            $response->addRoute(
                $route['from'],
                $route['to'],
                $route['depDate'],
                $route['flightClass'],
                $route['passengersCount'],
                $route['parsers'],
                $route['fullSearch'] ?? false
            );
        }

        return $response;
    }

    /**
     * @param string[] $routes
     * @param string[] $dates
     * @param string[] $flightClasses
     */
    private static function request(
        array $routes,
        array $dates,
        array $flightClasses,
        int $passengersCount = 1
    ): ParserSelectorRequest {
        $request = new ParserSelectorRequest();

        foreach ($routes as $route) {
            [$from, $to] = explode('-', $route);
            $request->addRoute(trim($from), trim($to));
        }

        foreach ($dates as $date) {
            $request->addDate(date_create($date));
        }

        $request->addFlightClasses($flightClasses);
        $request->addPassengersCount($passengersCount);

        return $request;
    }

    private static function route(
        string $from,
        string $to,
        string $depDate,
        string $flightClass,
        int $passengersCount,
        array $parsers,
        bool $fullSearch = false
    ): array {
        sort($parsers);

        return [
            'from' => $from,
            'to' => $to,
            'depDate' => date_create($depDate),
            'flightClass' => $flightClass,
            'passengersCount' => $passengersCount,
            'parsers' => $parsers,
            'fullSearch' => $fullSearch,
        ];
    }

    private static function period(string $date): int
    {
        return date_create($date)->format('W');
    }
}
