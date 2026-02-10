<?php

namespace AwardWallet\MainBundle\Service\Tripit\Serializer;

use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

class AddressObject
{
    /**
     * @Assert\NotBlank()
     * @Type("string")
     */
    private string $address = '';
    /**
     * @Type("string")
     */
    private string $city = '';
    /**
     * @Type("string")
     */
    private string $state = '';
    /**
     * @Type("string")
     */
    private string $zip = '';
    /**
     * @Type("string")
     */
    private string $country = '';
    /**
     * @Type("string")
     */
    private string $latitude = '';
    /**
     * @Type("string")
     */
    private string $longitude = '';

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getZip(): string
    {
        return $this->zip;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }
}
