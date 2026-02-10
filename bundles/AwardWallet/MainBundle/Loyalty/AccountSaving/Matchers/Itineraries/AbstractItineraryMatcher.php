<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Itineraries;

use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;
use AwardWallet\MainBundle\Service\Itinerary\Converter\SchemaConverter\Helper;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

abstract class AbstractItineraryMatcher implements ItineraryMatcherInterface
{
    protected Helper $helper;

    protected GeoLocationMatcher $locationMatcher;

    protected LoggerInterface $logger;

    public function __construct(Helper $helper, GeoLocationMatcher $locationMatcher, LoggerInterface $logger)
    {
        $this->helper = $helper;
        $this->locationMatcher = $locationMatcher;
        $this->logger = $logger;
    }

    public function supports(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): bool
    {
        $supportedEntityClass = $this->getSupportedEntityClass();
        $supportedSchemaClass = $this->getSupportedSchemaClass();

        return $entityItinerary instanceof $supportedEntityClass && $schemaItinerary instanceof $supportedSchemaClass;
    }

    public function match(EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): float
    {
        if (!$this->supports($entityItinerary, $schemaItinerary)) {
            throw new \InvalidArgumentException(sprintf("Expected %s and %s, got %s and %s", $this->getSupportedEntityClass(), $this->getSupportedSchemaClass(), get_class($entityItinerary), get_class($schemaItinerary)));
        }

        return 0;
    }

    public static function filterConfirmationNumber(string $number): string
    {
        return strtolower(str_replace('-', '', $number));
    }

    abstract protected function getSupportedEntityClass(): string;

    abstract protected function getSupportedSchemaClass(): string;
}
