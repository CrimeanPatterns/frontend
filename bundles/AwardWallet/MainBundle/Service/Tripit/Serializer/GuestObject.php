<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;

class GuestObject
{
    /**
     * @var string
     * @Type("string")
     */
    private $first_name;
    /**
     * @var string
     * @Type("string")
     */
    private $middle_name;
    /**
     * @var string
     * @Type("string")
     */
    private $last_name;
    /**
     * @var string
     * @Type("string")
     */
    private $frequent_traveler_num;
    /**
     * @var string
     * @Type("string")
     */
    private $frequent_traveler_supplier;

    public function getFirstName(): string
    {
        return ucfirst(strtolower($this->first_name));
    }

    public function getMiddleName(): string
    {
        return ucfirst(strtolower($this->middle_name));
    }

    public function getLastName(): string
    {
        return ucfirst(strtolower($this->last_name));
    }

    public function getFrequentTravelerNum()
    {
        return $this->frequent_traveler_num;
    }

    public function getFrequentTravelerSupplier()
    {
        return $this->frequent_traveler_supplier;
    }
}
