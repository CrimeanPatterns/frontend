<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Offerkindrefused.
 *
 * @ORM\Table(name="OfferKindRefused")
 * @ORM\Entity
 */
class Offerkindrefused
{
    /**
     * @var int
     * @ORM\Column(name="OfferKindRefusedID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $offerkindrefusedid;

    /**
     * @var int
     * @ORM\Column(name="UserID", type="integer", nullable=false)
     */
    protected $userid;

    /**
     * @var int
     * @ORM\Column(name="OfferKind", type="integer", nullable=true)
     */
    protected $offerkind;

    /**
     * Get offerkindrefusedid.
     *
     * @return int
     */
    public function getOfferkindrefusedid()
    {
        return $this->offerkindrefusedid;
    }

    /**
     * Set userid.
     *
     * @param int $userid
     * @return Offerkindrefused
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
     * Set offerkind.
     *
     * @param int $offerkind
     * @return Offerkindrefused
     */
    public function setOfferkind($offerkind)
    {
        $this->offerkind = $offerkind;

        return $this;
    }

    /**
     * Get offerkind.
     *
     * @return int
     */
    public function getOfferkind()
    {
        return $this->offerkind;
    }
}
