<?php

namespace AwardWallet\MainBundle\Worker\PushNotification\DTO;

class Options
{
    public const FLAG_DRY_RUN = 1 << 0;

    /**
     * @var int
     */
    protected $deadlineTimestamp;
    /**
     * @var int
     */
    protected $delay;
    /**
     * @var bool
     */
    protected $autoClose;
    /**
     * @var int
     */
    protected $flags = 0;
    /**
     * @var array
     */
    protected $logContext = [];
    /**
     * @var string
     */
    protected $minMobileVersion;
    /**
     * @var int
     */
    protected $priority = 1;
    /**
     * @var string
     */
    protected $interruptionLevel;

    /**
     * @return int
     */
    public function getDeadlineTimestamp()
    {
        return $this->deadlineTimestamp;
    }

    /**
     * @param int $deadlineTimestamp unixtime
     * @return Options
     */
    public function setDeadlineTimestamp($deadlineTimestamp)
    {
        $this->deadlineTimestamp = $deadlineTimestamp;

        return $this;
    }

    /**
     * @return int seconds
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * @param int $delay seconds
     * @return Options
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoClose()
    {
        return $this->autoClose;
    }

    /**
     * @param bool $autoClose
     * @return Options
     */
    public function setAutoClose($autoClose)
    {
        $this->autoClose = $autoClose;

        return $this;
    }

    /**
     * @return int
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @param int $flags
     * @return $this
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;

        return $this;
    }

    /**
     * @param int $flag
     * @return bool
     */
    public function hasFlag($flag)
    {
        return isset($this->flags) && ($flag === ($this->flags & $flag));
    }

    /**
     * @param int $flag
     * @return $this
     */
    public function addFlag($flag)
    {
        $this->flags |= $flag;

        return $this;
    }

    /**
     * @return array
     */
    public function getLogContext()
    {
        return $this->logContext;
    }

    /**
     * @return Options
     */
    public function setLogContext(array $logContext)
    {
        $this->logContext = $logContext;

        return $this;
    }

    /**
     * @return string
     */
    public function getMinMobileVersion()
    {
        return $this->minMobileVersion;
    }

    /**
     * @param string $minMobileVersion
     * @return Options
     */
    public function setMinMobileVersion($minMobileVersion)
    {
        $this->minMobileVersion = $minMobileVersion;

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
     * @return Options
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    public function getInterruptionLevel(): ?string
    {
        return $this->interruptionLevel;
    }

    public function setInterruptionLevel(string $interruptionLevel): self
    {
        $this->interruptionLevel = $interruptionLevel;

        return $this;
    }
}
