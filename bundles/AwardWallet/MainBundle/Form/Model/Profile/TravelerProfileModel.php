<?php

namespace AwardWallet\MainBundle\Form\Model\Profile;

use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;

class TravelerProfileModel extends AbstractEntityAwareModel
{
    /**
     * @var \DateTime
     */
    protected $dateOfBirth;

    /**
     * @var string
     */
    protected $seatPreference;

    /**
     * @var string
     */
    protected $mealPreference;

    /**
     * @var string
     */
    protected $homeAirport;

    public function getDateOfBirth(): ?\DateTime
    {
        return $this->dateOfBirth;
    }

    public function setDateOfBirth(?\DateTime $dateOfBirth): TravelerProfileModel
    {
        $this->dateOfBirth = $dateOfBirth;

        return $this;
    }

    public function getSeatPreference(): ?string
    {
        return $this->seatPreference;
    }

    public function setSeatPreference(?string $seatPreference): TravelerProfileModel
    {
        $this->seatPreference = $seatPreference;

        return $this;
    }

    public function getMealPreference(): ?string
    {
        return $this->mealPreference;
    }

    public function setMealPreference(?string $mealPreference): TravelerProfileModel
    {
        $this->mealPreference = $mealPreference;

        return $this;
    }

    public function getHomeAirport(): ?string
    {
        return $this->homeAirport;
    }

    public function setHomeAirport(?string $homeAirport): TravelerProfileModel
    {
        $this->homeAirport = $homeAirport;

        return $this;
    }
}
