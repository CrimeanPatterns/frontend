<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'HistoryRow'.
 */
class HistoryRow
{
    /**
     * @var HistoryField[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\HistoryField>")
     */
    private $fields;

    /**
     * @param HistoryField[] $fields
     */
    public function __construct($fields = [])
    {
        $this->fields = $fields;
    }

    /**
     * @param array
     * @return $this
     */
    public function setFields($fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }
}
