<?php

namespace AwardWallet\MainBundle\Loyalty\HistoryState;

use JMS\Serializer\Annotation\Discriminator;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\Type;

/**
 * @Discriminator(field = "structureVersion",
 * map = {
 * 		1: "AwardWallet\MainBundle\Loyalty\HistoryState\StructureVersion1"
 * })
 */
abstract class HistoryState
{
    public const ACTUAL_VERSION = StructureVersion1::class;
    public const MINIMAL_VERSION_NUMBER = 1;

    /**
     * JMS Serializer Discriminator works only on field with name "type".
     *
     * @var int
     * @Type("integer")
     * @SerializedName("structureVersion")
     */
    protected $type;

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
