<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Adtypemail.
 *
 * @ORM\Table(name="AdTypeMail")
 * @ORM\Entity
 */
class Adtypemail
{
    /**
     * @var int
     * @ORM\Column(name="AdTypeMailID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $adtypemailid;

    /**
     * @var string
     * @ORM\Column(name="TypeMail", type="string", length=250, nullable=false)
     */
    protected $typemail;

    /**
     * @var \Socialad
     * @ORM\ManyToOne(targetEntity="Socialad")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SocialAdID", referencedColumnName="SocialAdID")
     * })
     */
    protected $socialadid;

    /**
     * Get adtypemailid.
     *
     * @return int
     */
    public function getAdtypemailid()
    {
        return $this->adtypemailid;
    }

    /**
     * Set typemail.
     *
     * @param string $typemail
     * @return Adtypemail
     */
    public function setTypemail($typemail)
    {
        $this->typemail = $typemail;

        return $this;
    }

    /**
     * Get typemail.
     *
     * @return string
     */
    public function getTypemail()
    {
        return $this->typemail;
    }

    /**
     * Set socialadid.
     *
     * @return Adtypemail
     */
    public function setSocialadid(?Socialad $socialadid = null)
    {
        $this->socialadid = $socialadid;

        return $this;
    }

    /**
     * Get socialadid.
     *
     * @return \AwardWallet\MainBundle\Entity\Socialad
     */
    public function getSocialadid()
    {
        return $this->socialadid;
    }
}
