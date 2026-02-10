<?php

namespace AwardWallet\Tests\Modules\DbBuilder;

class Trip extends AbstractItinerary
{
    /**
     * @var TripSegment[]
     */
    private array $segments;

    /**
     * @param TripSegment[] $segments
     * @param User|UserAgent|null $user
     * @param TripSegment[] $segments
     */
    public function __construct(?string $recordLocator, array $segments, $user = null, array $fields = [])
    {
        parent::__construct(array_merge($fields, [
            'RecordLocator' => $recordLocator,
        ]));

        $this->user = $user;
        $this->segments = $segments;
    }

    /**
     * @return TripSegment[]
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    public function setSegments(array $segments): self
    {
        $this->segments = $segments;

        return $this;
    }

    public function addSegment(TripSegment $segment): self
    {
        $this->segments[] = $segment;

        return $this;
    }
}
