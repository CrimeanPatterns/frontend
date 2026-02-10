<?php

namespace AwardWallet\MainBundle\Service\FlightInfo\Response\Common;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class FlightStatus
{
    private $carrierIATACode;
    private $carrierICAOCode;

    private $flightNumber;

    private $departureLocalDate;
    private $departureUTCDate;
    private $arrivalLocalDate;
    private $arrivalUTCDate;

    private $departureIATACode;
    private $departureICAOCode;

    private $arrivalIATACode;
    private $arrivalICAOCode;

    private $info;

    private $createDate;

    /**
     * @return string
     */
    public function getCarrierIATACode()
    {
        return $this->carrierIATACode;
    }

    /**
     * @param string $IATACode
     */
    public function setCarrierIATACode($IATACode)
    {
        $this->carrierIATACode = $IATACode;
    }

    /**
     * @return string
     */
    public function getCarrierICAOCode()
    {
        return $this->carrierICAOCode;
    }

    /**
     * @param string $carrierICAOCode
     */
    public function setCarrierICAOCode($carrierICAOCode)
    {
        $this->carrierICAOCode = $carrierICAOCode;
    }

    /**
     * @return int
     */
    public function getFlightNumber()
    {
        return $this->flightNumber;
    }

    public function setFlightNumber($flightNumber)
    {
        $this->flightNumber = intval($flightNumber);
    }

    /**
     * @return \DateTime
     */
    public function getDepartureLocalDate()
    {
        return $this->departureLocalDate;
    }

    public function setDepartureLocalDate(\DateTime $depDate)
    {
        $this->departureLocalDate = $depDate;
    }

    /**
     * @return \DateTime
     */
    public function getDepartureUTCDate()
    {
        return $this->departureUTCDate;
    }

    /**
     * @param \DateTime $departureUTCDate
     */
    public function setDepartureUTCDate($departureUTCDate)
    {
        $this->departureUTCDate = $departureUTCDate;
    }

    /**
     * @return \DateTime
     */
    public function getArrivalLocalDate()
    {
        return $this->arrivalLocalDate;
    }

    /**
     * @param \DateTime $arrivalLocalDate
     */
    public function setArrivalLocalDate($arrivalLocalDate)
    {
        $this->arrivalLocalDate = $arrivalLocalDate;
    }

    /**
     * @return \DateTime
     */
    public function getArrivalUTCDate()
    {
        return $this->arrivalUTCDate;
    }

    /**
     * @param \DateTime $arrivalUTCDate
     */
    public function setArrivalUTCDate($arrivalUTCDate)
    {
        $this->arrivalUTCDate = $arrivalUTCDate;
    }

    /**
     * @return string
     */
    public function getDepartureIATACode()
    {
        return $this->departureIATACode;
    }

    /**
     * @param string $departureIATACode
     */
    public function setDepartureIATACode($departureIATACode)
    {
        $this->departureIATACode = $departureIATACode;
    }

    /**
     * @return string
     */
    public function getDepartureICAOCode()
    {
        return $this->departureICAOCode;
    }

    /**
     * @param string $departureICAOCode
     */
    public function setDepartureICAOCode($departureICAOCode)
    {
        $this->departureICAOCode = $departureICAOCode;
    }

    /**
     * @return string
     */
    public function getArrivalIATACode()
    {
        return $this->arrivalIATACode;
    }

    /**
     * @param string $arrivalIATACode
     */
    public function setArrivalIATACode($arrivalIATACode)
    {
        $this->arrivalIATACode = $arrivalIATACode;
    }

    /**
     * @return string
     */
    public function getArrivalICAOCode()
    {
        return $this->arrivalICAOCode;
    }

    /**
     * @param string $arrivalICAOCode
     */
    public function setArrivalICAOCode($arrivalICAOCode)
    {
        $this->arrivalICAOCode = $arrivalICAOCode;
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param array $info
     */
    public function setInfo($info)
    {
        $this->info = $info;

        if (isset($info['DepDate'])) {
            $this->departureLocalDate = new \DateTime($info['DepDate']);
        }

        if (isset($info['DepDateUtc'])) {
            $this->departureUTCDate = new \DateTime($info['DepDateUtc']);
        }

        if (isset($info['ArrDate'])) {
            $this->arrivalLocalDate = new \DateTime($info['ArrDate']);
        }

        if (isset($info['ArrDateUtc'])) {
            $this->arrivalUTCDate = new \DateTime($info['ArrDateUtc']);
        }
    }

    /**
     * @return bool
     */
    public function isIATA()
    {
        return (bool) ($this->carrierIATACode && $this->departureIATACode && $this->arrivalIATACode);
    }

    /**
     * @return bool
     */
    public function isICAO()
    {
        return (bool) ($this->carrierICAOCode && $this->departureICAOCode && $this->arrivalICAOCode);
    }

    /**
     * @return bool
     */
    public function isLocal()
    {
        return (bool) $this->departureLocalDate;
    }

    /**
     * @return bool
     */
    public function isUTC()
    {
        return (bool) $this->departureUTCDate;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTime $createDate)
    {
        $this->createDate = $createDate;
    }
}
