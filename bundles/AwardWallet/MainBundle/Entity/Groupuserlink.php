<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Groupuserlink.
 *
 * @ORM\Table(name="GroupUserLink")
 * @ORM\Entity
 */
class Groupuserlink
{
    /**
     * @var int
     * @ORM\Column(name="GroupUserLinkID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $groupuserlinkid;

    /**
     * @var bool
     * @ORM\Column(name="IsPrimary", type="boolean", nullable=true)
     */
    protected $isprimary;

    /**
     * @var \Sitegroup
     * @ORM\ManyToOne(targetEntity="Sitegroup")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="SiteGroupID", referencedColumnName="SiteGroupID")
     * })
     */
    protected $sitegroupid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr", inversedBy="groups")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get groupuserlinkid.
     *
     * @return int
     */
    public function getGroupuserlinkid()
    {
        return $this->groupuserlinkid;
    }

    /**
     * Set isprimary.
     *
     * @param bool $isprimary
     * @return Groupuserlink
     */
    public function setIsprimary($isprimary)
    {
        $this->isprimary = $isprimary;

        return $this;
    }

    /**
     * Get isprimary.
     *
     * @return bool
     */
    public function getIsprimary()
    {
        return $this->isprimary;
    }

    /**
     * Set sitegroupid.
     *
     * @return Groupuserlink
     */
    public function setSitegroupid(?Sitegroup $sitegroupid = null)
    {
        $this->sitegroupid = $sitegroupid;

        return $this;
    }

    /**
     * Get sitegroupid.
     *
     * @return \AwardWallet\MainBundle\Entity\Sitegroup
     */
    public function getSitegroupid()
    {
        return $this->sitegroupid;
    }

    /**
     * Set userid.
     *
     * @return Groupuserlink
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
