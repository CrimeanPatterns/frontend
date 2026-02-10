<?php

namespace AwardWallet\Tests\FunctionalSymfony\MainBundle\Service\RA\Flight;

use AwardWallet\MainBundle\Entity\RAFlightSearchQuery;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\AppBot\AppBot;
use AwardWallet\MainBundle\Service\LogProcessor;
use AwardWallet\MainBundle\Service\RA\Flight\LoggerFactory;
use AwardWallet\Tests\FunctionalSymfony\BaseTraitCest;
use AwardWallet\Tests\FunctionalSymfony\Traits\StaffUser;
use AwardWallet\Tests\Modules\AutoVerifyMocksTrait;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractCest extends BaseTraitCest
{
    use AutoVerifyMocksTrait;
    use StaffUser;

    protected ?RouterInterface $router;

    protected ?TestLogger $logger;

    protected array $notifications = [];

    public function _before(\TestSymfonyGuy $I)
    {
        parent::_before($I);

        $this->router = $I->grabService('router');
        $this->logger = new TestLogger();
        $I->mockService(LoggerFactory::class, $I->stubMake(LoggerFactory::class, [
            'createProcessor' => function () {
                return new LogProcessor();
            },
            'createLogger' => $this->logger,
        ]));
        $I->mockService(AppBot::class, $I->stubMake(AppBot::class, [
            'send' => function (string $channelName, array $message) {
                $this->notifications[] = $message['blocks'][0]['text']['text'];
            },
        ]));
        $I->mockService(\Memcached::class, $I->stubMake(\Memcached::class, [
            'get' => false,
            'set' => true,
        ]));

        $I->executeQuery('DELETE FROM RAFlightSearchQuery');
    }

    public function _after(\TestSymfonyGuy $I)
    {
        parent::_after($I);

        $this->router = null;
        $this->notifications = [];
        $this->logger = null;
    }

    protected function sendCallbackRequest(\TestSymfonyGuy $I, array $data = []): void
    {
        $I->amHttpAuthenticated('awardwallet', $I->grabParameter('reward_availability_callback_pass'));
        $I->send('POST', $this->router->generate('aw_ra_flight_search_result'), json_encode($data));
    }

    protected function apiCustomRoute(
        array $segments,
        int $cost,
        string $flightDuration,
        int $tickets
    ): array {
        return $this->apiRoute(
            array_map(function (array $segment) {
                return $this->apiSegment(
                    $this->apiAirport($segment['dep'][0], new \DateTime($segment['dep'][1])),
                    $this->apiAirport($segment['arr'][0], new \DateTime($segment['arr'][1])),
                    $this->apiTimes($segment['duration']),
                    'Meal',
                    $segment['cabin'],
                    'Fare class',
                    ['123'],
                    'AA',
                    'Aircraft'
                );
            }, $segments),
            $this->apiMileCost($cost, 'Test program'),
            $this->apiCashCost('USD', 1, 10, 20),
            $this->apiTimes($flightDuration),
            $tickets,
            count($segments) - 1,
        );
    }

    protected function apiLax2Jfk(
        ?int $queryId = 1,
        ?int $mileCost = 100,
        ?string $flightDuration = '1:30',
        ?string $layoverDuration = null,
        ?int $tickets = 1,
        ?string $parser = 'test',
        ?array $cashCost = null
    ): array {
        return [
            'response' => [
                $this->apiResponse(
                    StringHandler::getRandomCode(10),
                    $queryId,
                    $parser,
                    [
                        $this->apiRoute(
                            [
                                $this->apiSegment(
                                    $this->apiAirport('LAX', new \DateTime('+3 days 00:00:00'), '1'),
                                    $this->apiAirport('JFK', new \DateTime('+3 days 01:30:00'), '2'),
                                    $this->apiTimes($flightDuration, $layoverDuration),
                                    'Meal',
                                    'economy',
                                    'Fare class',
                                    ['123'],
                                    'AA',
                                    'Aircraft'
                                ),
                            ],
                            $this->apiMileCost($mileCost, 'Test program'),
                            $cashCost ?? $this->apiCashCost('USD', 1, 10, 20),
                            $this->apiTimes($flightDuration, $layoverDuration),
                            $tickets,
                            0,
                            'Economy'
                        ),
                    ]
                ),
            ],
        ];
    }

    protected function apiResponse(
        ?string $requestId = null,
        ?int $queryId = null,
        ?string $parser = null,
        ?array $routes = null
    ): array {
        return [
            'requestId' => $requestId,
            'requestDate' => date('Y-m-d H:i:s'),
            'state' => 1,
            'userData' => json_encode(['id' => $queryId, 'parser' => $parser]),
            'routes' => $routes,
        ];
    }

    protected function apiRoute(
        ?array $segments = null,
        ?array $mileCost = null,
        ?array $cashCost = null,
        ?array $times = null,
        ?int $tickets = null,
        ?int $numberOfStops = null,
        ?string $awardTypes = null
    ): array {
        return [
            'segments' => $segments,
            'mileCost' => $mileCost,
            'cashCost' => $cashCost,
            'times' => $times,
            'tickets' => $tickets,
            'numberOfStops' => $numberOfStops,
            'awardTypes' => $awardTypes,
        ];
    }

    protected function apiSegment(
        ?array $departure = null,
        ?array $arrival = null,
        ?array $times = null,
        ?string $meal = null,
        ?string $cabin = null,
        ?string $fareClass = null,
        ?array $flightNumbers = null,
        ?string $airlineCode = null,
        ?string $aircraft = null
    ): array {
        return [
            'departure' => $departure,
            'arrival' => $arrival,
            'times' => $times,
            'meal' => $meal,
            'cabin' => $cabin,
            'fareClass' => $fareClass,
            'flightNumbers' => $flightNumbers,
            'airlineCode' => $airlineCode,
            'aircraft' => $aircraft,
        ];
    }

    protected function apiAirport(?string $airport = null, ?\DateTime $dateTime = null, ?string $terminal = null): array
    {
        return [
            'airport' => $airport,
            'dateTime' => $dateTime ? $dateTime->format('Y-m-d H:i:s') : null,
            'terminal' => $terminal,
        ];
    }

    protected function apiMileCost(?int $miles = null, ?string $program = null): array
    {
        return [
            'miles' => $miles,
            'program' => $program,
        ];
    }

    protected function apiCashCost(
        ?string $currency = null,
        ?float $conversionRate = null,
        ?float $taxes = null,
        ?float $fees = null
    ): array {
        return [
            'currency' => $currency,
            'conversionRate' => $conversionRate,
            'taxes' => $taxes,
            'fees' => $fees,
        ];
    }

    protected function apiTimes(?string $flightDuration = null, ?string $layoverDuration = null): array
    {
        return [
            'flight' => $flightDuration,
            'layover' => $layoverDuration,
        ];
    }

    protected function addSearchQuery(\TestSymfonyGuy $I, array $data = []): int
    {
        return $I->haveInDatabase('RAFlightSearchQuery', array_merge([
            'UserID' => $this->user->getId(),
            'DepartureAirports' => json_encode(['LAX']),
            'ArrivalAirports' => json_encode(['JFK']),
            'DepDateFrom' => date('Y-m-d', strtotime('+3 days')),
            'DepDateTo' => date('Y-m-d', strtotime('+5 days')),
            'FlightClass' => RAFlightSearchQuery::FLIGHT_CLASS_BUSINESS,
            'Adults' => 1,
            'SearchInterval' => RAFlightSearchQuery::SEARCH_INTERVAL_ONCE,
            'Parsers' => 'test',
            'EconomyMilesLimit' => null,
            'PremiumEconomyMilesLimit' => null,
            'BusinessMilesLimit' => null,
            'FirstMilesLimit' => null,
        ], $data));
    }

    protected function clearLogs(): void
    {
        $this->logger->reset();
        $this->notifications = [];
    }
}
