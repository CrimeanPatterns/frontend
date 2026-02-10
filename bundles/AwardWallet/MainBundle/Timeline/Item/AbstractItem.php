<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

abstract class AbstractItem implements ItemInterface, \JsonSerializable
{
    protected int $id;

    protected \DateTime $startDate;

    protected ?\DateTime $endDate = null;

    protected ?\DateTime $localDate = null;

    /**
     * can we set past/future breakpoint after this item?
     */
    protected bool $breakAfter;

    protected Context $context;

    public function __construct(int $id, \DateTime $startDate, ?\DateTime $endDate = null, ?\DateTime $localDate = null, bool $breakAfter = true)
    {
        $this->id = $id;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->localDate = $localDate;
        $this->breakAfter = $breakAfter;
        $this->context = new Context();
    }

    public function getId(): string
    {
        return sprintf('%s.%d', $this->getPrefix(), $this->id);
    }

    public function setStartDate(\DateTime $date): self
    {
        $this->startDate = $date;

        return $this;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setEndDate(?\DateTime $date): self
    {
        $this->endDate = $date;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setLocalDate(?\DateTime $date): self
    {
        $this->localDate = $date;

        return $this;
    }

    public function getLocalDate(): ?\DateTime
    {
        return $this->localDate;
    }

    public function getTimezoneAbbr(): ?string
    {
        return null;
    }

    public function setBreakAfter(bool $breakAfter): self
    {
        $this->breakAfter = $breakAfter;

        return $this;
    }

    public function isBreakAfter(): bool
    {
        return $this->breakAfter;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function jsonSerialize()
    {
        $encoder = new JsonEncoder();

        return $encoder->decode($encoder->encode(get_object_vars($this), 'json'), 'json');
    }
}
