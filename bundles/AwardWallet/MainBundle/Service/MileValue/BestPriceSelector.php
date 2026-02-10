<?php

namespace AwardWallet\MainBundle\Service\MileValue;

use AwardWallet\Common\Airport\AirportTime;
use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Service\AirportCity;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\Price;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\ResultRoute;
use AwardWallet\MainBundle\Service\MileValue\PriceSource\SearchRoute;
use Psr\Log\LoggerInterface;

class BestPriceSelector
{
    private AirportTime $airportTime;

    private LoggerInterface $logger;

    private AirportCity $airportCity;

    private AirlineRepository $airlineRepository;
    private $unknownAirlines = [];
    private $knownAirlines = [];

    public function __construct(
        AirportTime $airportTime,
        LoggerInterface $logger,
        AirportCity $airportCity,
        AirlineRepository $airlineRepository
    ) {
        $this->airportTime = $airportTime;
        $this->logger = $logger;
        $this->airportCity = $airportCity;
        $this->airlineRepository = $airlineRepository;
    }

    /**
     * @param Price[] $prices
     * @return PriceWithInfo[]
     */
    public function getBestPriceList(array $prices, array $searchRoutes, int $originalTripDuration, string $classOfService): array
    {
        if (!in_array($classOfService, Constants::CLASSES_OF_SERVICE)) {
            throw new \Exception("Unknown class of service");
        }

        $this->logger->info("selecting best price from " . count($prices) . " prices");
        $priceInfos = $this->convertPricesToPriceInfos($prices, $searchRoutes);

        usort($priceInfos, function (PriceWithInfo $a, PriceWithInfo $b) {
            return $a->price->price <=> $b->price->price;
        });

        $originalStops = array_reduce($searchRoutes, function (array $carry, SearchRoute $route): array {
            return array_merge($carry, [$route->depCode, $route->arrCode]);
        }, []);

        $priceInfos = array_filter($priceInfos, function (PriceWithInfo $priceWithInfo) use ($classOfService, $originalTripDuration, $originalStops): bool {
            $containsTaxi = array_reduce($priceWithInfo->price->routes, function (bool $carry, ResultRoute $route): bool {
                return $carry || stripos($route->airline, 'taxi') !== false;
            }, false);

            $routeStops = array_reduce($priceWithInfo->price->routes, function (array $carry, ResultRoute $route): array {
                return array_merge($carry, [$this->airportCity->findCity($route->depCode), $this->airportCity->findCity($route->arrCode)]);
            }, []);

            return
                (
                    $priceWithInfo->duration <= $originalTripDuration
                    || (($priceWithInfo->duration - $originalTripDuration) / $originalTripDuration) <= 0.2
                    || $originalStops === $routeStops
                )
                && !($priceWithInfo->lowCoster && in_array($classOfService, Constants::LUXE_CLASSES_OF_SERVICE))
                && !$containsTaxi
            ;
        });

        $this->logger->info("got " . count($priceInfos) . " best prices after filtering");

        return $priceInfos;
    }

    /**
     * calc duration excluding stops time.
     */
    public function calcDuration(array $routes, array $stops): int
    {
        $lastCity = null;
        $lastArrival = null;

        return array_reduce($routes, function (int $carry, ResultRoute $route) use (&$lastArrival, &$lastCity, $stops): int {
            $depTime = $this->airportTime->convertToGmt($route->depDate, $route->depCode);
            $arrTime = $this->airportTime->convertToGmt($route->arrDate, $route->arrCode);

            if ($lastCity !== null && !in_array($lastCity, $stops)) {
                $carry += $depTime - $lastArrival;
            }

            $lastCity = $this->airportCity->findCity($route->arrCode);
            $lastArrival = $arrTime;

            return $carry + ($arrTime - $depTime);
        }, 0);
    }

    private function calcLowCoster(array $routes): bool
    {
        return array_reduce($routes, function (int $carry, ResultRoute $route): bool {
            return $carry || in_array($route->airline, Constants::LOWCOSTERS);
        }, false);
    }

    private function logStats(array $priceInfos)
    {
        $prices = array_map(function (PriceWithInfo $priceWithInfo) { return $priceWithInfo->price->price; }, $priceInfos);
        $durations = array_map(function (PriceWithInfo $priceWithInfo) { return $priceWithInfo->duration; }, $priceInfos);

        if (count($prices) === 0) {
            $this->logger->info("no prices to select from");
        } else {
            $this->logger->info("found " . count($priceInfos) . " prices, from \$" . round(min($prices),
                2) . " to \$" . round(max($prices),
                    2) . ", duration " . TimeDiff::format(min($durations)) . " to " . TimeDiff::format(max($durations)));
        }
    }

    private function correctAirlineName(string $nameOrCode): string
    {
        /** @var Airline $airline */
        if (isset($this->knownAirlines[$nameOrCode])) {
            return $this->knownAirlines[$nameOrCode];
        }

        $airline = $this->airlineRepository->search(null, $nameOrCode, $nameOrCode);

        if ($airline === null || empty($airline->getCode())) {
            if (!in_array($nameOrCode, $this->unknownAirlines)) {
                $this->logger->warning("airline not found: {$nameOrCode}");
                $this->unknownAirlines[] = $nameOrCode;
            }

            return $nameOrCode;
        }

        $this->knownAirlines[$nameOrCode] = $airline->getCode();

        return $airline->getCode();
    }

    /**
     * @param Price[] $prices
     * @param SearchRoute[] $searchRoutes
     * @return PriceWithInfo[]
     */
    private function convertPricesToPriceInfos(array $prices, array $searchRoutes): array
    {
        $stops = array_unique(array_reduce($searchRoutes, function (array $carry, SearchRoute $route): array {
            return array_merge($carry, [$route->depCode, $route->arrCode]);
        }, []));

        $priceInfos = array_map(function (Price $price) use ($stops): PriceWithInfo {
            foreach ($price->routes as $route) {
                $route->airline = $this->correctAirlineName($route->airline);
            }

            return new PriceWithInfo(
                $price,
                $this->calcDuration($price->routes, $stops),
                $this->calcLowCoster($price->routes)
            );
        }, $prices);

        $this->logStats($priceInfos);

        return $priceInfos;
    }
}
