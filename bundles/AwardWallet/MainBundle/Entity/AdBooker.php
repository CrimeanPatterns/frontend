<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AdBooker.
 *
 * @ORM\Table(name="AdBooker")
 * @ORM\Entity
 */
class AdBooker
{
    /**
     * @var int
     * @ORM\Column(name="AdBookerID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $AdBookerID;

    /**
     * @var \AwardWallet\MainBundle\Entity\Usr
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $Booker;

    /**
     * @var \AwardWallet\MainBundle\Entity\Socialad
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Socialad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SocialAdID", referencedColumnName="SocialAdID")
     * })
     */
    protected $SocialAd;

    /**
     * @return int
     */
    public function getAdBookerID()
    {
        return $this->AdBookerID;
    }

    /**
     * @param int $AdBookerID
     */
    public function setAdBookerID($AdBookerID)
    {
        $this->AdBookerID = $AdBookerID;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getBooker()
    {
        return $this->Booker;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\Usr $Booker
     */
    public function setBooker($Booker)
    {
        $this->Booker = $Booker;
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\Socialad
     */
    public function getSocialAd()
    {
        return $this->SocialAd;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\Socialad $SocialAd
     */
    public function setSocialAd($SocialAd)
    {
        $this->SocialAd = $SocialAd;
    }
}
