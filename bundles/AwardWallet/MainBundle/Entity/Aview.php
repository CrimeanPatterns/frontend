<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Aview.
 *
 * @ORM\Table(name="AView")
 * @ORM\Entity
 */
class Aview
{
    /**
     * @var int
     * @ORM\Column(name="AViewID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $aviewid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=20, nullable=false)
     */
    protected $name;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get aviewid.
     *
     * @return int
     */
    public function getAviewid()
    {
        return $this->aviewid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Aview
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set userid.
     *
     * @return Aview
     */
    public function setUserid(?Usr $userid = null)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * Get userid.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserid()
    {
        return $this->userid;
    }
}
