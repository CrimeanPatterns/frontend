<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Offerrocketmilesshown.
 *
 * @ORM\Table(name="OfferRocketmilesShown")
 * @ORM\Entity
 */
class Offerrocketmilesshown
{
    /**
     * @var int
     * @ORM\Column(name="OfferRocketmilesShownID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $offerrocketmilesshownid;

    /**
     * @var int
     * @ORM\Column(name="UserID", type="integer", nullable=false)
     */
    protected $userid;

    /**
     * @var int
     * @ORM\Column(name="RecordID", type="integer", nullable=false)
     */
    protected $recordid;

    /**
     * @var string
     * @ORM\Column(name="RecordType", type="string", length=1, nullable=false)
     */
    protected $recordtype;

    /**
     * Get offerrocketmilesshownid.
     *
     * @return int
     */
    public function getOfferrocketmilesshownid()
    {
        return $this->offerrocketmilesshownid;
    }

    /**
     * Set userid.
     *
     * @param int $userid
     * @return Offerrocketmilesshown
     */
    public function setUserid($userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid.
     *
     * @return int
     */
    public function getUserid()
    {
        return $this->userid;
    }

    /**
     * Set recordid.
     *
     * @param int $recordid
     * @return Offerrocketmilesshown
     */
    public function setRecordid($recordid)
    {
        $this->recordid = $recordid;

        return $this;
    }

    /**
     * Get recordid.
     *
     * @return int
     */
    public function getRecordid()
    {
        return $this->recordid;
    }

    /**
     * Set recordtype.
     *
     * @param string $recordtype
     * @return Offerrocketmilesshown
     */
    public function setRecordtype($recordtype)
    {
        $this->recordtype = $recordtype;

        return $this;
    }

    /**
     * Get recordtype.
     *
     * @return string
     */
    public function getRecordtype()
    {
        return $this->recordtype;
    }
}
