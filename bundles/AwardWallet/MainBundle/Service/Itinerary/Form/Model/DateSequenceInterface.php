<?php

namespace AwardWallet\MainBundle\Service\Itinerary\Form\Model;

use AwardWallet\Common\Entity\Aircode;

interface DateSequenceInterface
{
    public function getStartDate(): ?\DateTime;

    /**
     * @return string|Aircode|null
     */
    public function getStartLocation();

    public function getEndDate(): ?\DateTime;

    /**
     * @return string|Aircode|null
     */
    public function getEndLocation();

    public function getDateSequenceViolationMessage(): string;

    public function getDateSequenceViolationPath(): string;
}
