<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\Entity\Repositories\AirlineRepository;
use AwardWallet\MainBundle\Service\MileValue\AirportCountryDetector;
use AwardWallet\MainBundle\Service\MileValue\Constants;
use AwardWallet\MainBundle\Service\MileValue\FareClassMapper;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CombinedPriceSource implements PriceSourceInterface
{
    private SkyScannerLivePriceSource $skyScannerLivePriceSource;

    private KiwiPriceSource $kiwiPriceSource;

    private FareClassMapper $fareClassMapper;

    private AirlineRepository $airlineRepository;

    public function __construct(
        SkyScannerLivePriceSource $skyScannerLivePriceSource,
        KiwiPriceSource $kiwiPriceSource,
        LoggerInterface $logger,
        AirportCountryDetector $airportCountryDetector,
        FareClassMapper $fareClassMapper,
        AirlineRepository $airlineRepository
    ) {
        $this->skyScannerLivePriceSource = $skyScannerLivePriceSource;
        $this->kiwiPriceSource = $kiwiPriceSource;
        $this->fareClassMapper = $fareClassMapper;
        $this->airlineRepository = $airlineRepository;
    }

    public function search(array $routes, string $classOfService, int $passengers): array
    {
        $economy = in_array($classOfService, Constants::ECONOMY_CLASSES);

        // $results = $this->skyScannerLivePriceSource->search($routes, $classOfService, $passengers);
        $results = $this->kiwiPriceSource->search($routes, $classOfService, $passengers);
        $results = array_filter($results, [$this, "convertFareClassToClassOfService"]);

        if ($classOfService === Constants::CLASS_BASIC_ECONOMY || !$economy) {
            return $results;
        }

        return $this->increaseBasicEconomyPrices($results, (RouteTypeDetector::detect($routes) === Constants::ROUTE_TYPE_ROUND_TRIP ? 60 : 30) * $passengers);
    }

    private function convertFareClassToClassOfService(Price $price): Price
    {
        foreach ($price->routes as $route) {
            if ($route->classOfService !== null) {
                continue;
            }
            $airline = $this->airlineRepository->findOneBy(['code' => $route->airline]);

            if ($airline === null) {
                continue;
            }
            $route->classOfService = $this->fareClassMapper->map($airline->getAirlineid(), $route->fareClass ?? '');
        }

        return $price;
    }

    private function increaseBasicEconomyPrices(array $results, int $increment): array
    {
        return
            it($results)
            ->map(function (Price $price) use ($increment) {
                $allEconomy = array_reduce($price->routes, function (bool $carry, ResultRoute $route): bool {
                    if (!$carry) {
                        return false;
                    }

                    if (!in_array($route->classOfService, [Constants::CLASS_ECONOMY, Constants::CLASS_ECONOMY_PLUS, Constants::CLASS_PREMIUM_ECONOMY])) {
                        return false;
                    }

                    return true;
                }, true);

                if (!$allEconomy) {
                    $price->price += $increment;
                    $price->priceAdjustment = $increment;
                }

                return $price;
            })
            ->toArray();
    }
}
