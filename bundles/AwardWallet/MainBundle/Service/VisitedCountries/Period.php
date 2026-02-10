<?php

namespace AwardWallet\MainBundle\Service\VisitedCountries;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI
 */
class Period
{
    public string $country;
    public ?\DateTime $startDate;
    public ?\DateTime $endDate;

    public function __construct(string $country, ?\DateTime $startDate = null, ?\DateTime $endDate = null)
    {
        $this->country = $country;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function getDays(): ?int
    {
        if (is_null($this->startDate) || is_null($this->endDate)) {
            return null;
        }

        $from = clone $this->startDate;
        $to = clone $this->endDate;
        $from->setTime(00, 00, 00);
        $to->setTime(00, 00, 00);

        return $from->diff($to)->days;
    }
}
