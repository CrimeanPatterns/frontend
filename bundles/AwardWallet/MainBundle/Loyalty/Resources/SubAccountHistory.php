<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class SubAccountHistory
{
    /**
     * @var string
     * @Type("string")
     */
    private $code;

    /**
     * @var HistoryRow[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\HistoryRow>")
     */
    private $rows;

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return HistoryRow[]
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @param HistoryRow[] $rows
     * @return $this
     */
    public function setRows($rows)
    {
        $this->rows = $rows;

        return $this;
    }
}
