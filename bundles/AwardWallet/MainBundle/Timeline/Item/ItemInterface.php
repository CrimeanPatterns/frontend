<?php

namespace AwardWallet\MainBundle\Timeline\Item;

interface ItemInterface
{
    public function getId(): string;

    public function getPrefix(): string;

    public function getType(): string;

    public function setStartDate(\DateTime $date);

    public function getStartDate(): \DateTime;

    public function setEndDate(?\DateTime $date);

    public function getEndDate(): ?\DateTime;

    public function setLocalDate(?\DateTime $date);

    public function getLocalDate(): ?\DateTime;

    public function getTimezoneAbbr(): ?string;

    public function setBreakAfter(bool $breakAfter);

    public function isBreakAfter(): bool;

    public function getContext(): Context;
}
