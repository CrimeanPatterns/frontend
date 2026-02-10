<?php

namespace AwardWallet\MainBundle\Entity\Type;

use AwardWallet\MainBundle\Entity\AbRequest;

/**
 * Class AbMessageMetadata.
 */
class AbMessageMetadata
{
    protected $Ref;

    protected $Status;
    protected $Reason;

    protected $Color;

    protected $CPR; // CustomProgramRequested
    protected $APR; // AccountProgramRequested

    /**
     * @var int
     */
    protected $invoiceId;

    /**
     * @var float
     */
    protected $totalInvoice;

    /**
     * @return int
     */
    public function getRef()
    {
        return $this->Ref;
    }

    /**
     * @param int $Ref
     */
    public function setRef($Ref)
    {
        $this->Ref = $Ref;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        if (!in_array($this->Status, AbRequest::STATUSES)) {
            return AbRequest::BOOKING_STATUS_CANCELED;
        }

        return $this->Status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->Status = $status;
    }

    /**
     * @return null
     */
    public function getReason()
    {
        return $this->Reason;
    }

    /**
     * @param null $reason
     */
    public function setReason($reason)
    {
        $this->Reason = $reason;
    }

    public function toArray()
    {
        $result = [];

        if (isset($this->Ref)) {
            $result['Ref'] = $this->Ref;
        }

        if (isset($this->Status)) {
            $result['Status'] = $this->Status;
        }

        if (isset($this->Reason)) {
            $result['Reason'] = $this->Reason;
        }

        if (isset($this->Color)) {
            $result['Color'] = $this->Color;
        }

        if (isset($this->APR)) {
            $result['APR'] = $this->APR;
        }

        if (isset($this->CPR)) {
            $result['CPR'] = $this->CPR;
        }

        if (isset($this->invoiceId)) {
            $result['invoiceId'] = $this->invoiceId;
        }

        if (isset($this->totalInvoice)) {
            $result['totalInvoice'] = $this->totalInvoice;
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->Color;
    }

    /**
     * @param string $Color
     */
    public function setColor($Color)
    {
        $this->Color = $Color;
    }

    /**
     * @return array
     */
    public function getRequested()
    {
        return [
            'account' => $this->APR,
            'custom' => $this->CPR,
        ];
    }

    /**
     * @param array $requested
     */
    public function setRequested($requested = [])
    {
        if (array_key_exists('custom', $requested)) {
            $this->CPR = $requested['custom'];
        }

        if (array_key_exists('account', $requested)) {
            $this->APR = $requested['account'];
        }
    }

    /**
     * @return array
     */
    public function getCPR()
    {
        return is_array($this->CPR) ? $this->CPR : [];
    }

    /**
     * @param array $CPR
     */
    public function setCPR($CPR)
    {
        $this->CPR = $CPR;
    }

    /**
     * @return array
     */
    public function getAPR()
    {
        return is_array($this->APR) ? $this->APR : [];
    }

    /**
     * @param array $APR
     */
    public function setAPR($APR)
    {
        $this->APR = $APR;
    }

    public function getInvoiceId(): ?int
    {
        if (isset($this->invoiceId)) {
            return (int) $this->invoiceId;
        }

        return null;
    }

    public function setInvoiceId(int $invoiceId)
    {
        $this->invoiceId = $invoiceId;
    }

    public function getTotalInvoice(): ?float
    {
        if (isset($this->totalInvoice)) {
            return (float) $this->totalInvoice;
        }

        return null;
    }

    public function setTotalInvoice(float $totalInvoice)
    {
        $this->totalInvoice = $totalInvoice;
    }
}
