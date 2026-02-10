<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class History
{
    public const HISTORY_COMPLETE = 'complete';
    public const HISTORY_INCREMENTAL = 'incremental';
    public const HISTORY_INCREMENTAL2 = 'incremental2';

    /**
     * @var string
     * @Type("string")
     */
    private $range;

    /**
     * Base64 crypted HistoryState object.
     *
     * @var string
     * @Type("string")
     */
    private $state;

    /**
     * @var HistoryRow[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\HistoryRow>")
     */
    private $rows;

    /**
     * @var SubAccountHistory[]
     * @Type("array<AwardWallet\MainBundle\Loyalty\Resources\SubAccountHistory>")
     */
    private $subAccounts;

    /**
     * @return string
     */
    public function getRange()
    {
        return $this->range;
    }

    /**
     * @param string $range
     * @return $this
     */
    public function setRange($range)
    {
        $this->range = $range;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

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

    /**
     * @return SubAccountHistory[]
     */
    public function getSubAccounts()
    {
        return $this->subAccounts;
    }

    /**
     * @param SubAccountHistory[] $subAccounts
     * @return $this
     */
    public function setSubAccounts($subAccounts)
    {
        $this->subAccounts = $subAccounts;

        return $this;
    }
}
