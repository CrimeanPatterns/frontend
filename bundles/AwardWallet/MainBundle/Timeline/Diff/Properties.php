<?php

namespace AwardWallet\MainBundle\Timeline\Diff;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Globals\StringUtils;

class Properties
{
    /**
     * like 'TS123' for TripSegment with id 123.
     *
     * @var string
     */
    public $sourceId;

    /**
     * @var \DateTime
     */
    public $expirationDate;

    /**
     * like ['Gate' => '17', 'Seats' => '12, 13'].
     *
     * @var array
     */
    public $values;

    /**
     * where this property came from.
     *
     * @var PropertySourceInterface
     */
    public $source;

    public $userData;

    /**
     * @param \DateTime $expirationDate
     */
    public function __construct(PropertySourceInterface $source, string $sourceId, \DateTimeInterface $expirationDate, array $values, $userData = null)
    {
        $this->source = $source;
        $this->sourceId = $sourceId;
        $this->expirationDate = $expirationDate;
        $this->values = $this->filterEmpty($values);
        $this->userData = $userData;
    }

    public static function trimValue($value)
    {
        return trim($value, "-");
    }

    /**
     * @return Itinerary
     */
    public function getEntity()
    {
        return $this->source->getEntity($this);
    }

    private function filterEmpty(array $values)
    {
        $result = [];

        foreach ($values as $key => $value) {
            if (StringUtils::isNotEmpty(self::trimValue($value))) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
