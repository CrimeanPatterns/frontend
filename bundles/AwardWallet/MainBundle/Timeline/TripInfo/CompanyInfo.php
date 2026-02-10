<?php

namespace AwardWallet\MainBundle\Timeline\TripInfo;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Provider;

class CompanyInfo
{
    public ?string $companyName = null;

    public ?string $companyCode = null;

    /**
     * @var Provider|Airline
     */
    public $companyObject;

    /**
     * @param object|string $airline
     */
    public function __construct($airline)
    {
        if (\is_null($airline)) {
            throw new \InvalidArgumentException('Airline should be provided');
        }

        if (
            !\is_string($airline)
            && !($airline instanceof Airline)
            && !($airline instanceof Provider)
        ) {
            throw new \InvalidArgumentException('$airline should either string or object of types [' . Airline::class . ', ' . Provider::class . ']');
        }

        if (\is_string($airline)) {
            $this->companyName = $airline;
        } else {
            $this->companyObject = $airline;

            if ($airline instanceof Airline) {
                $this->companyName = $airline->getName();
                $this->companyCode = $airline->getCode();
            } elseif ($airline instanceof Provider) {
                $this->companyName = $airline->getShortname();
            }
        }
    }

    public function equals(self $airlineInfo): bool
    {
        if (isset($airlineInfo->companyCode, $this->companyCode)) {
            return $airlineInfo->companyCode === $this->companyCode;
        }

        if (isset($airlineInfo->companyName, $this->companyName)) {
            return $airlineInfo->companyName === $this->companyName;
        }

        return false;
    }
}
