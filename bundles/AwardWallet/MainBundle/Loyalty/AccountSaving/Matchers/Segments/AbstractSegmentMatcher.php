<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Matchers\Segments;

use AwardWallet\MainBundle\Entity\Tripsegment as EntitySegment;
use AwardWallet\MainBundle\Service\GeoLocationMatcher;

abstract class AbstractSegmentMatcher implements SegmentMatcherInterface
{
    protected GeoLocationMatcher $locationMatcher;

    public function __construct(GeoLocationMatcher $locationMatcher)
    {
        $this->locationMatcher = $locationMatcher;
    }

    public function supports(EntitySegment $entitySegment, $schemaSegment): bool
    {
        $supportedSchemaClass = $this->getSupportedSchemaClass();

        return $schemaSegment instanceof $supportedSchemaClass;
    }

    public function match(EntitySegment $entitySegment, $schemaSegment, string $scope): float
    {
        if (!$this->supports($entitySegment, $schemaSegment)) {
            throw new \InvalidArgumentException(sprintf("Expected %s and %s, got %s and %s", $this->getSupportedEntityClass(), $this->getSupportedSchemaClass(), get_class($entitySegment), get_class($schemaSegment)));
        }

        return 0;
    }

    abstract protected function getSupportedEntityClass(): string;

    abstract protected function getSupportedSchemaClass(): string;
}
