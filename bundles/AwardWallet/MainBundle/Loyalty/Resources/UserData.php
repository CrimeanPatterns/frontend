<?php
/**
 * Created by PhpStorm.
 * User: puzakov
 * Date: 31/10/2016
 * Time: 14:56.
 */

namespace AwardWallet\MainBundle\Loyalty\Resources;

use JMS\Serializer\Annotation\Type;

class UserData
{
    /**
     * @var int
     * @Type("integer")
     */
    private $accountId;

    /**
     * @var int
     * @Type("integer")
     */
    private $priority;

    /**
     * @var int - one of UpdaterEngineInterface::SOURCE_ constants
     * @Type("integer")
     */
    private $source;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $checkIts;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $checkPastIts = false;

    /**
     * @var bool
     * @Type("boolean")
     */
    private $otcWait;

    public function __construct($accountId = null)
    {
        $this->accountId = $accountId;
    }

    public function __toString()
    {
        return json_encode(get_object_vars($this), true);
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     * @return $this
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param int $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCheckIts()
    {
        return $this->checkIts;
    }

    /**
     * @param bool $checkIts
     * @return $this
     */
    public function setCheckIts($checkIts)
    {
        $this->checkIts = $checkIts;

        return $this;
    }

    public function isCheckPastIts(): bool
    {
        return $this->checkPastIts;
    }

    public function setCheckPastIts(bool $checkPastIts): self
    {
        $this->checkPastIts = $checkPastIts;

        return $this;
    }

    /**
     * @return bool
     */
    public function getOtcWait()
    {
        return $this->otcWait;
    }

    /**
     * @return $this
     */
    public function setOtcWait(bool $otcWait)
    {
        $this->otcWait = $otcWait;

        return $this;
    }
}
