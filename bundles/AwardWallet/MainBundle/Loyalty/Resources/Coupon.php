<?php

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

/**
 * Generated resource DTO for 'Coupon'.
 */
class Coupon
{
    /**
     * @var string
     * @Type("string")
     */
    private $id;

    /**
     * @var string
     * @Type("string")
     */
    private $file;

    /**
     * @var string
     * @Type("string")
     */
    private $caption;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $used;

    /**
     * @var date
     * @Type("date")
     */
    private $expiresAt;

    /**
     * @var date
     * @Type("date")
     */
    private $purchasedAt;

    /**
     * @var string
     * @Type("string")
     */
    private $status;

    /**
     * @param string
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * @param bool
     * @return $this
     */
    public function setUsed($used)
    {
        $this->used = $used;

        return $this;
    }

    /**
     * @param date
     * @return $this
     */
    public function setExpiresat($expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * @param date
     * @return $this
     */
    public function setPurchasedat($purchasedAt)
    {
        $this->purchasedAt = $purchasedAt;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @return bool
     */
    public function getUsed()
    {
        return $this->used;
    }

    /**
     * @return date
     */
    public function getExpiresat()
    {
        return $this->expiresAt;
    }

    /**
     * @return date
     */
    public function getPurchasedat()
    {
        return $this->purchasedAt;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
