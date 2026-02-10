<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Redirecthit.
 *
 * @ORM\Table(name="RedirectHit")
 * @ORM\Entity
 */
class Redirecthit
{
    /**
     * @var int
     * @ORM\Column(name="RedirectHitID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $redirecthitid;

    /**
     * @var \DateTime
     * @ORM\Column(name="HitDate", type="datetime", nullable=false)
     */
    protected $hitdate;

    /**
     * @var int
     * @ORM\Column(name="UserID", type="integer", nullable=true)
     */
    protected $userid;

    /**
     * @var \Redirect
     * @ORM\ManyToOne(targetEntity="Redirect")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="RedirectID", referencedColumnName="RedirectID")
     * })
     */
    protected $redirectid;

    /**
     * Get redirecthitid.
     *
     * @return int
     */
    public function getRedirecthitid()
    {
        return $this->redirecthitid;
    }

    /**
     * Set hitdate.
     *
     * @param \DateTime $hitdate
     * @return Redirecthit
     */
    public function setHitdate($hitdate)
    {
        $this->hitdate = $hitdate;

        return $this;
    }

    /**
     * Get hitdate.
     *
     * @return \DateTime
     */
    public function getHitdate()
    {
        return $this->hitdate;
    }

    /**
     * Set userid.
     *
     * @param int $userid
     * @return Redirecthit
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
     * Set redirectid.
     *
     * @return Redirecthit
     */
    public function setRedirectid(?Redirect $redirectid = null)
    {
        $this->redirectid = $redirectid;

        return $this;
    }

    /**
     * Get redirectid.
     *
     * @return \AwardWallet\MainBundle\Entity\Redirect
     */
    public function getRedirectid()
    {
        return $this->redirectid;
    }
}
