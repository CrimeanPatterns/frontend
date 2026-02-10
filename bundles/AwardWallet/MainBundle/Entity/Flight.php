<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Flight.
 *
 * @ORM\Table(name="Flight")
 * @ORM\Entity
 */
class Flight
{
    /**
     * @var int
     * @ORM\Column(name="FlightID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $flightid;

    /**
     * @var string
     * @ORM\Column(name="OperatorName", type="string", length=250, nullable=false)
     */
    protected $operatorname;

    /**
     * @var string
     * @ORM\Column(name="OperatorCode", type="string", length=80, nullable=false)
     */
    protected $operatorcode;

    /**
     * @var int
     * @ORM\Column(name="Length", type="integer", nullable=false)
     */
    protected $length = 0;

    /**
     * @var float
     * @ORM\Column(name="Fare", type="decimal", nullable=false)
     */
    protected $fare = 0;

    /**
     * @var float
     * @ORM\Column(name="Fee", type="decimal", nullable=false)
     */
    protected $fee = 0;

    /**
     * @var \DateTime
     * @ORM\Column(name="OutboundDepartDate", type="datetime", nullable=true)
     */
    protected $outbounddepartdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="InboundArriveDate", type="datetime", nullable=true)
     */
    protected $inboundarrivedate;

    /**
     * @var \DateTime
     * @ORM\Column(name="InboundDepartDate", type="datetime", nullable=true)
     */
    protected $inbounddepartdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="OutboundArriveDate", type="datetime", nullable=true)
     */
    protected $outboundarrivedate;

    /**
     * @var int
     * @ORM\Column(name="Stops", type="integer", nullable=true)
     */
    protected $stops;

    /**
     * @var int
     * @ORM\Column(name="SequenceNumber", type="integer", nullable=false)
     */
    protected $sequencenumber = 0;

    /**
     * @var \Flightquery
     * @ORM\ManyToOne(targetEntity="Flightquery")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="FlightQueryID", referencedColumnName="FlightQueryID")
     * })
     */
    protected $flightqueryid;

    /**
     * Get flightid.
     *
     * @return int
     */
    public function getFlightid()
    {
        return $this->flightid;
    }

    /**
     * Set operatorname.
     *
     * @param string $operatorname
     * @return Flight
     */
    public function setOperatorname($operatorname)
    {
        $this->operatorname = $operatorname;

        return $this;
    }

    /**
     * Get operatorname.
     *
     * @return string
     */
    public function getOperatorname()
    {
        return $this->operatorname;
    }

    /**
     * Set operatorcode.
     *
     * @param string $operatorcode
     * @return Flight
     */
    public function setOperatorcode($operatorcode)
    {
        $this->operatorcode = $operatorcode;

        return $this;
    }

    /**
     * Get operatorcode.
     *
     * @return string
     */
    public function getOperatorcode()
    {
        return $this->operatorcode;
    }

    /**
     * Set length.
     *
     * @param int $length
     * @return Flight
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length.
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set fare.
     *
     * @param float $fare
     * @return Flight
     */
    public function setFare($fare)
    {
        $this->fare = $fare;

        return $this;
    }

    /**
     * Get fare.
     *
     * @return float
     */
    public function getFare()
    {
        return $this->fare;
    }

    /**
     * Set fee.
     *
     * @param float $fee
     * @return Flight
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Get fee.
     *
     * @return float
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set outbounddepartdate.
     *
     * @param \DateTime $outbounddepartdate
     * @return Flight
     */
    public function setOutbounddepartdate($outbounddepartdate)
    {
        $this->outbounddepartdate = $outbounddepartdate;

        return $this;
    }

    /**
     * Get outbounddepartdate.
     *
     * @return \DateTime
     */
    public function getOutbounddepartdate()
    {
        return $this->outbounddepartdate;
    }

    /**
     * Set inboundarrivedate.
     *
     * @param \DateTime $inboundarrivedate
     * @return Flight
     */
    public function setInboundarrivedate($inboundarrivedate)
    {
        $this->inboundarrivedate = $inboundarrivedate;

        return $this;
    }

    /**
     * Get inboundarrivedate.
     *
     * @return \DateTime
     */
    public function getInboundarrivedate()
    {
        return $this->inboundarrivedate;
    }

    /**
     * Set inbounddepartdate.
     *
     * @param \DateTime $inbounddepartdate
     * @return Flight
     */
    public function setInbounddepartdate($inbounddepartdate)
    {
        $this->inbounddepartdate = $inbounddepartdate;

        return $this;
    }

    /**
     * Get inbounddepartdate.
     *
     * @return \DateTime
     */
    public function getInbounddepartdate()
    {
        return $this->inbounddepartdate;
    }

    /**
     * Set outboundarrivedate.
     *
     * @param \DateTime $outboundarrivedate
     * @return Flight
     */
    public function setOutboundarrivedate($outboundarrivedate)
    {
        $this->outboundarrivedate = $outboundarrivedate;

        return $this;
    }

    /**
     * Get outboundarrivedate.
     *
     * @return \DateTime
     */
    public function getOutboundarrivedate()
    {
        return $this->outboundarrivedate;
    }

    /**
     * Set stops.
     *
     * @param int $stops
     * @return Flight
     */
    public function setStops($stops)
    {
        $this->stops = $stops;

        return $this;
    }

    /**
     * Get stops.
     *
     * @return int
     */
    public function getStops()
    {
        return $this->stops;
    }

    /**
     * Set sequencenumber.
     *
     * @param int $sequencenumber
     * @return Flight
     */
    public function setSequencenumber($sequencenumber)
    {
        $this->sequencenumber = $sequencenumber;

        return $this;
    }

    /**
     * Get sequencenumber.
     *
     * @return int
     */
    public function getSequencenumber()
    {
        return $this->sequencenumber;
    }

    /**
     * Set flightqueryid.
     *
     * @return Flight
     */
    public function setFlightqueryid(?Flightquery $flightqueryid = null)
    {
        $this->flightqueryid = $flightqueryid;

        return $this;
    }

    /**
     * Get flightqueryid.
     *
     * @return \AwardWallet\MainBundle\Entity\Flightquery
     */
    public function getFlightqueryid()
    {
        return $this->flightqueryid;
    }
}
