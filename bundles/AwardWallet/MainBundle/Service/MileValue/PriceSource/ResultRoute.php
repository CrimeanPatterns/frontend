<?php

namespace AwardWallet\MainBundle\Service\MileValue\PriceSource;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation\Type;

/**
 * @NoDI()
 */
class ResultRoute
{
    /**
     * @Type("string")
     * @var string
     */
    public $depCode;
    /**
     * @Type("string")
     * @var string
     */
    public $arrCode;
    /**
     * @Type("int")
     * @var int
     */
    public $depDate;
    /**
     * @Type("int")
     * @var int
     */
    public $arrDate;
    /**
     * @Type("string")
     * @var string - code or name
     */
    public $airline;
    /**
     * @Type("string")
     * @var string
     */
    public $flightNumber;
    /**
     * @Type("string")
     * @var string|null
     */
    public $classOfService;
    /**
     * @Type("string")
     * @var string|null
     */
    public $operatingAirline;
    /**
     * @Type("string")
     * @var string|null
     */
    public $operatingFlightNumber;
    /**
     * @Type("string")
     * @var string|null
     */
    public $fareClass;
    /**
     * @Type("string")
     * @var string|null
     */
    public $fareBasis;

    public function __construct(
        string $depCode,
        string $arrCode,
        int $depDate,
        int $arrDate,
        string $airline,
        string $flightNumber,
        ?string $classOfService = null,
        ?string $operatingAirline = null,
        ?string $operatingFlightNumber = null,
        ?string $fareClass = null,
        ?string $fareBasis = null
    ) {
        $this->depCode = $depCode;
        $this->arrCode = $arrCode;
        $this->depDate = $depDate;
        $this->arrDate = $arrDate;
        $this->airline = $airline;
        $this->flightNumber = $flightNumber;
        $this->classOfService = $classOfService;
        $this->operatingAirline = $operatingAirline;
        $this->operatingFlightNumber = $operatingFlightNumber;
        $this->fareClass = $fareClass;
        $this->fareBasis = $fareBasis;
    }
}
