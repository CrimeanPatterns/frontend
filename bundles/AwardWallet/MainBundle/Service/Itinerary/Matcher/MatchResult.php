<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Matcher;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Itinerary as EntityItinerary;
use AwardWallet\Schema\Itineraries\Itinerary as SchemaItinerary;
use Psr\Log\LoggerInterface;

/**
 * @NoDI
 */
class MatchResult
{
    private array $results = [];

    public function addResult(string $keyMessage, bool $result, float $confidence): self
    {
        $this->results[$keyMessage] = [
            'result' => $result,
            'confidence' => $confidence,
        ];

        return $this;
    }

    public function merge(MatchResult $matchResult): self
    {
        $this->results = array_merge($this->results, $matchResult->results);

        return $this;
    }

    public function maxConfidence(): float
    {
        $max = 0;

        foreach ($this->results as $result) {
            if ($result['result']) {
                $max = \max($max, $result['confidence']);
            }
        }

        return $max;
    }

    public function writeLogs(LoggerInterface $logger, EntityItinerary $entityItinerary, SchemaItinerary $schemaItinerary): void
    {
        $logger->info(sprintf('itinerary "%s" match result', $entityItinerary->getIdString()), [
            'entity' => ObjectToArrayConverter::convertItinerary($entityItinerary),
            'schema' => $schemaItinerary,
            'results' => $this->results,
        ]);
    }

    public static function create(): self
    {
        return new self();
    }
}
