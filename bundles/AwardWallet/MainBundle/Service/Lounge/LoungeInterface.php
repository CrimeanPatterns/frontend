<?php

namespace AwardWallet\MainBundle\Service\Lounge;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Alliance;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\AbstractOpeningHours;
use Doctrine\Common\Collections\Collection;

interface LoungeInterface
{
    public function getName(): ?string;

    public function getAirportCode(): ?string;

    public function getTerminal(): ?string;

    public function getGate(): ?string;

    public function getGate2(): ?string;

    public function getOpeningHours(): ?AbstractOpeningHours;

    public function isAvailable(): ?bool;

    public function getLocation(): ?string;

    public function getAdditionalInfo(): ?string;

    public function getAmenities(): ?string;

    public function getRules(): ?string;

    public function isRestaurant(): ?bool;

    public function getCreateDate(): ?\DateTime;

    public function getUpdateDate(): ?\DateTime;

    public function isPriorityPassAccess(): ?bool;

    public function isAmexPlatinumAccess(): ?bool;

    public function isDragonPassAccess(): ?bool;

    public function isLoungeKeyAccess(): ?bool;

    /**
     * @return Airline[]|Collection
     */
    public function getAirlines();

    /**
     * @return Alliance[]|Collection
     */
    public function getAlliances();
}
