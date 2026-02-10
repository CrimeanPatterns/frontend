<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * AwardWallet\MainBundle\Entity\AbSegment.
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name="AbSegment",
 *     indexes={
 * @ORM\Index(name="IDX_F77EB7AB18FCD26A", columns={"RequestID"}),
 * @ORM\Index(name="IDX_F77EB7AB29E8B855", columns={"Dep"}),
 * @ORM\Index(name="IDX_F77EB7AB829ED401", columns={"Arr"})
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Assert\Callback("isValid")
 */
class AbSegment
{
    public const ROUNDTRIP_ONEWAY = 2;
    public const ROUNDTRIP_ROUND = 1;
    public const ROUNDTRIP_MULTIPLE = 0;
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $AbSegmentID;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $DepDateFrom = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $DepDateTo = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $DepDateIdeal = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     * @Assert\Type(type="bool")
     */
    protected $DepDateFlex;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $ReturnDateFrom = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $ReturnDateTo = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $ReturnDateIdeal = null;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     * @Assert\Type(type="bool")
     */
    protected $ReturnDateFlex;

    /**
     * @var int
     * @ORM\Column(type="integer", length=1, nullable=false)
     */
    protected $Priority = 1;

    /**
     * @var int
     * @ORM\Column(type="integer", length=1, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Range(min = 0, max = 2)
     */
    protected $RoundTrip = self::ROUNDTRIP_ROUND;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $RoundTripDaysIdeal;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     * @Assert\Type(type="bool")
     */
    protected $RoundTripDaysFlex;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $RoundTripDaysFrom;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $RoundTripDaysTo;

    /**
     * @var AbRequest
     * @ORM\ManyToOne(targetEntity="AbRequest", inversedBy="Segments")
     * @ORM\JoinColumn(name="RequestID", referencedColumnName="AbRequestID", nullable=false)
     */
    protected $RequestID;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "250", allowEmptyString="true")
     */
    protected $Dep;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Assert\Type(type="bool")
     */
    protected $DepCheckOtherAirports = false;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "250", allowEmptyString="true")
     */
    protected $Arr;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Assert\Type(type="bool")
     */
    protected $ArrCheckOtherAirports = false;

    /**
     * Get AbSegmentID.
     *
     * @return int
     */
    public function getAbSegmentID()
    {
        return $this->AbSegmentID;
    }

    public function setDepDateFrom(?\DateTime $depDateFrom): self
    {
        $this->DepDateFrom = $depDateFrom;

        return $this;
    }

    public function getDepDateFrom(): ?\DateTime
    {
        return $this->DepDateFrom;
    }

    public function setDepDateTo(?\DateTime $depDateTo): self
    {
        $this->DepDateTo = $depDateTo;

        return $this;
    }

    public function getDepDateTo(): ?\DateTime
    {
        return $this->DepDateTo;
    }

    public function setDepDateIdeal(?\DateTime $depDateIdeal): self
    {
        $this->DepDateIdeal = $depDateIdeal;

        return $this;
    }

    public function getDepDateIdeal(): ?\DateTime
    {
        return $this->DepDateIdeal;
    }

    public function setReturnDateFrom(?\DateTime $returnDateFrom): self
    {
        $this->ReturnDateFrom = $returnDateFrom;

        return $this;
    }

    public function getReturnDateFrom(): ?\DateTime
    {
        return $this->ReturnDateFrom;
    }

    public function setReturnDateTo(?\DateTime $returnDateTo): self
    {
        $this->ReturnDateTo = $returnDateTo;

        return $this;
    }

    public function getReturnDateTo(): ?\DateTime
    {
        return $this->ReturnDateTo;
    }

    public function setReturnDateIdeal(?\DateTime $returnDateIdeal): self
    {
        $this->ReturnDateIdeal = $returnDateIdeal;

        return $this;
    }

    public function getReturnDateIdeal(): ?\DateTime
    {
        return $this->ReturnDateIdeal;
    }

    /**
     * Set Priority.
     *
     * @param int $priority
     * @return AbSegment
     */
    public function setPriority($priority)
    {
        $this->Priority = $priority;

        return $this;
    }

    /**
     * Get Priority.
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->Priority;
    }

    /**
     * Set RoundTrip.
     *
     * @param int $roundTrip
     * @return AbSegment
     */
    public function setRoundTrip($roundTrip)
    {
        $this->RoundTrip = $roundTrip;

        return $this;
    }

    /**
     * Get RoundTrip.
     *
     * @return int
     */
    public function getRoundTrip()
    {
        return $this->RoundTrip;
    }

    public function isRoundTrip()
    {
        return $this->RoundTrip == self::ROUNDTRIP_ROUND;
    }

    /**
     * Set request.
     *
     * @return AbSegment
     */
    public function setRequest(AbRequest $request)
    {
        $this->RequestID = $request;

        return $this;
    }

    /**
     * Get request.
     *
     * @return \AwardWallet\MainBundle\Entity\AbRequest
     */
    public function getRequest()
    {
        return $this->RequestID;
    }

    /**
     * Set departure.
     *
     * @param string $Dep
     */
    public function setDep($Dep)
    {
        $this->Dep = $Dep;

        return $this;
    }

    /**
     * Get departure.
     *
     * @return string
     */
    public function getDep()
    {
        return $this->Dep;
    }

    public function isDepCheckOtherAirports(): bool
    {
        return $this->DepCheckOtherAirports;
    }

    public function setDepCheckOtherAirports(bool $DepCheckOtherAirports): self
    {
        $this->DepCheckOtherAirports = $DepCheckOtherAirports;

        return $this;
    }

    /**
     * Set arrival.
     *
     * @param string $Arr
     */
    public function setArr($Arr)
    {
        $this->Arr = $Arr;

        return $this;
    }

    /**
     * Get arrival.
     *
     * @return string
     */
    public function getArr()
    {
        return $this->Arr;
    }

    public function isArrCheckOtherAirports(): bool
    {
        return $this->ArrCheckOtherAirports;
    }

    public function setArrCheckOtherAirports(bool $ArrCheckOtherAirports): self
    {
        $this->ArrCheckOtherAirports = $ArrCheckOtherAirports;

        return $this;
    }

    public function getFrom()
    {
        if (mb_strlen($this->getDep()) > 14) {
            return substr($this->getDep(), 0, 14) . '...';
        } else {
            return $this->getDep();
        }
    }

    public function getTo()
    {
        if (mb_strlen($this->getArr()) > 14) {
            return substr($this->getArr(), 0, 14) . '...';
        } else {
            return $this->getArr();
        }
    }

    public function isValid(ExecutionContextInterface $context)
    {
        $depIdeal = $this->getDepDateIdeal();
        $depFrom = $this->getDepDateFrom();
        $depTo = $this->getDepDateTo();
        $returnIdeal = $this->getReturnDateIdeal();
        $returnFrom = $this->getReturnDateFrom();
        $returnTo = $this->getReturnDateTo();
        $isBlank = function ($value) {
            return false === $value || (empty($value) && '0' != $value);
        };

        if ($isBlank($depIdeal) && ($isBlank($depFrom) || $isBlank($depTo))) {
            $context
                ->buildViolation('This value should not be blank.')
                ->atPath('DepDateIdeal')
                ->addViolation();
        }

        if ($this->getRoundTrip() == self::ROUNDTRIP_ROUND) {
            if ($isBlank($returnIdeal) && ($isBlank($returnFrom) || $isBlank($returnTo))) {
                $context
                    ->buildViolation('This value should not be blank.')
                    ->atPath('ReturnDateIdeal')
                    ->addViolation();
            }
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function prePersist()
    {
        $segments = $this->RequestID->getSegments();
        $firstSegment = $segments->first();

        if ($firstSegment) {
            $isRound = $firstSegment->isRoundTrip();
        }

        /** @var AbSegment $segment */
        foreach ($segments as $segment) {
            $ideal = $segment->getDepDateIdeal();
            $from = $segment->getDepDateFrom();
            $to = $segment->getDepDateTo();

            if (!empty($ideal) && ((!empty($from) && empty($to)) || (empty($from) && !empty($to)))) {
                $d = (!empty($from)) ? $from : $to;
                $segment->setDepDateFrom($d);
                $segment->setDepDateTo($ideal);
            }

            if ($segment->getDepDateFrom() && $segment->getDepDateTo()) {
                if ($segment->getDepDateFrom() > $segment->getDepDateTo()) {
                    $from = $segment->getDepDateFrom();
                    $segment->setDepDateFrom($segment->getDepDateTo());
                    $segment->setDepDateTo($from);
                }
            } else {
                //                $segment->setDepDateFrom($ideal);
                //                $segment->setDepDateTo($ideal);
            }

            if ($isRound) {
                $ideal = $segment->getReturnDateIdeal();
                $from = $segment->getReturnDateFrom();
                $to = $segment->getReturnDateTo();

                if (!empty($ideal) && ((!empty($from) && empty($to)) || (empty($from) && !empty($to)))) {
                    $d = (!empty($from)) ? $from : $to;
                    $segment->setReturnDateFrom($d);
                    $segment->setReturnDateTo($ideal);
                }

                if ($segment->getReturnDateFrom() && $segment->getReturnDateTo()) {
                    if ($segment->getReturnDateFrom() > $segment->getReturnDateTo()) {
                        $from = $segment->getReturnDateFrom();
                        $segment->setReturnDateFrom($segment->getReturnDateTo());
                        $segment->setReturnDateTo($from);
                    }
                } else {
                    $segment->setReturnDateFrom($ideal);
                    $segment->setReturnDateTo($ideal);
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function isDepDateFlex()
    {
        return $this->DepDateFlex;
    }

    /**
     * @param bool $DepDateFlex
     * @return AbSegment
     */
    public function setDepDateFlex($DepDateFlex)
    {
        $this->DepDateFlex = $DepDateFlex;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReturnDateFlex()
    {
        return $this->ReturnDateFlex;
    }

    /**
     * @param bool $ReturnDateFlex
     * @return AbSegment
     */
    public function setReturnDateFlex($ReturnDateFlex)
    {
        $this->ReturnDateFlex = $ReturnDateFlex;

        return $this;
    }

    /**
     * @return int
     */
    public function getRoundTripDaysIdeal()
    {
        return $this->RoundTripDaysIdeal;
    }

    /**
     * @param int $RoundTripDaysIdeal
     */
    public function setRoundTripDaysIdeal($RoundTripDaysIdeal)
    {
        $this->RoundTripDaysIdeal = $RoundTripDaysIdeal;
    }

    /**
     * @return int
     */
    public function getRoundTripDaysFrom()
    {
        return $this->RoundTripDaysFrom;
    }

    /**
     * @param int $RoundTripDaysFrom
     */
    public function setRoundTripDaysFrom($RoundTripDaysFrom)
    {
        $this->RoundTripDaysFrom = $RoundTripDaysFrom;
    }

    /**
     * @return int
     */
    public function getRoundTripDaysTo()
    {
        return $this->RoundTripDaysTo;
    }

    /**
     * @param int $RoundTripDaysTo
     */
    public function setRoundTripDaysTo($RoundTripDaysTo)
    {
        $this->RoundTripDaysTo = $RoundTripDaysTo;
    }

    /**
     * @return int
     */
    public function getRoundTripDaysFlex()
    {
        return $this->RoundTripDaysFlex;
    }

    /**
     * @param int $RoundTripDaysFlex
     * @return AbSegment
     */
    public function setRoundTripDaysFlex($RoundTripDaysFlex)
    {
        $this->RoundTripDaysFlex = $RoundTripDaysFlex;

        return $this;
    }
}
