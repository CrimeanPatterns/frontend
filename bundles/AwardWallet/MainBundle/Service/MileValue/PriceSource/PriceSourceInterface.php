<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

interface PriceSourceInterface
{
    /**
     * @param SearchRoute[] $routes
     * @param string $classOfService - one of Constants::CLASSES_OF_SERVICE
     * @return Price[]
     */
    public function search(array $routes, string $classOfService, int $passengers): array;
}
