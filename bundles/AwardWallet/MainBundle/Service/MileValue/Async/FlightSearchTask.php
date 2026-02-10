<?php

namespace AwardWallet\MainBundle\Service\MileValue\Async;

use AwardWallet\MainBundle\Service\MileValue\PriceSource\SearchRoute;
use AwardWallet\MainBundle\Worker\AsyncProcess\Task;

class FlightSearchTask extends Task
{
    /**
     * @var array
     */
    private $priceSources;
    /**
     * @var SearchRoute[]
     */
    private $routes;
    /**
     * @var string
     */
    private $classOfService;
    /**
     * @var int
     */
    private $passengers;
    /**
     * @var string
     */
    private $responseChannel;
    /**
     * @var int
     */
    private $duration;

    /**
     * @param SearchRoute[] $routes
     */
    public function __construct(array $mileValuePriceSources, array $routes, string $classOfService, int $passengers, int $duration, string $responseChannel)
    {
        parent::__construct(FlightSearchExecutor::class, bin2hex(random_bytes(10)));
        $this->priceSources = $mileValuePriceSources;
        $this->routes = $routes;
        $this->classOfService = $classOfService;
        $this->passengers = $passengers;
        $this->responseChannel = $responseChannel;
        $this->duration = $duration;
    }

    public function getPriceSources(): array
    {
        return $this->priceSources;
    }

    /**
     * @return SearchRoute[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getClassOfService(): string
    {
        return $this->classOfService;
    }

    public function getPassengers(): int
    {
        return $this->passengers;
    }

    public function getResponseChannel(): string
    {
        return $this->responseChannel;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }
}
