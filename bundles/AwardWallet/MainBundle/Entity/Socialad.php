<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Socialad.
 *
 * @ORM\Table(name="SocialAd")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\SocialadRepository")
 */
class Socialad
{
    public const GEO_GROUP_ALL = 0;
    public const GEO_GROUP_US = 1 << 0;
    public const GEO_GROUP_NON_US = 1 << 1;

    public const GEO_GROUPS = [
        self::GEO_GROUP_ALL => 'All Users',
        self::GEO_GROUP_US => 'US Based users only',
        self::GEO_GROUP_NON_US => 'Non US Based',
    ];

    /**
     * @var int
     * @ORM\Column(name="SocialAdID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $socialadid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=80, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Content", type="string", length=4000, nullable=true)
     */
    protected $content;

    /**
     * @var \DateTime
     * @ORM\Column(name="BeginDate", type="datetime", nullable=true)
     */
    protected $begindate;

    /**
     * @var \DateTime
     * @ORM\Column(name="EndDate", type="datetime", nullable=true)
     */
    protected $enddate;

    /**
     * @var int
     * @ORM\Column(name="Kind", type="integer", nullable=false)
     */
    protected $kind = 1;

    /**
     * @var int
     * @ORM\Column(name="GeoGroups", type="integer", nullable=true)
     */
    protected $geoGroups;

    /**
     * @var int
     * @ORM\Column(name="AllProviders", type="integer", nullable=false)
     */
    protected $allproviders = 0;

    /**
     * @var bool
     * @ORM\Column(name="ProviderKind", type="boolean", nullable=true)
     */
    protected $providerkind;

    /**
     * @var string
     * @ORM\Column(name="InternalNote", type="string", nullable=true)
     */
    protected $internalnote;

    /**
     * Get socialadid.
     *
     * @return int
     */
    public function getSocialadid()
    {
        return $this->socialadid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Socialad
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
     * Set content.
     *
     * @param string $content
     * @return Socialad
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set begindate.
     *
     * @param \DateTime $begindate
     * @return Socialad
     */
    public function setBegindate($begindate)
    {
        $this->begindate = $begindate;

        return $this;
    }

    /**
     * Get begindate.
     *
     * @return \DateTime
     */
    public function getBegindate()
    {
        return $this->begindate;
    }

    /**
     * Set enddate.
     *
     * @param \DateTime $enddate
     * @return Socialad
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;

        return $this;
    }

    /**
     * Get enddate.
     *
     * @return \DateTime
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * Set kind.
     *
     * @param int $kind
     * @return Socialad
     */
    public function setKind($kind)
    {
        $this->kind = $kind;

        return $this;
    }

    /**
     * Get kind.
     *
     * @return int
     */
    public function getKind()
    {
        return $this->kind;
    }

    /**
     * @param int $group
     * @return bool
     */
    public function hasGeoGroup($group)
    {
        if ($group === self::GEO_GROUP_ALL && !is_int($this->geoGroups)) {
            return true;
        }

        if (!is_int($this->geoGroups)) {
            return false;
        }

        return ($this->geoGroups & $group) > 0;
    }

    /**
     * @param int $group
     */
    public function addGeoGroup($group)
    {
        if ($group === self::GEO_GROUP_ALL) {
            $this->geoGroups = null;

            return;
        }

        if (!is_int($this->geoGroups)) {
            $this->geoGroups = 0;
        }
        $this->geoGroups |= $group;
    }

    /**
     * @param int $group
     */
    public function removeGeoGroup($group)
    {
        if (!is_int($this->geoGroups)) {
            return;
        }
        $this->geoGroups &= ~$group;

        if ($this->geoGroups === 0) {
            $this->geoGroups = null;
        }
    }

    /**
     * @param int $groups
     * @return $this
     */
    public function setGeoGroups($groups)
    {
        if ($groups === self::GEO_GROUP_ALL) {
            $groups = null;
        }
        $this->geoGroups = $groups;

        return $this;
    }

    /**
     * @return int
     */
    public function getGeoGroups()
    {
        return $this->geoGroups;
    }

    /**
     * Set allproviders.
     *
     * @param int $allproviders
     * @return Socialad
     */
    public function setAllproviders($allproviders)
    {
        $this->allproviders = $allproviders;

        return $this;
    }

    /**
     * Get allproviders.
     *
     * @return int
     */
    public function getAllproviders()
    {
        return $this->allproviders;
    }

    /**
     * Set providerkind.
     *
     * @param bool $providerkind
     * @return Socialad
     */
    public function setProviderkind($providerkind)
    {
        $this->providerkind = $providerkind;

        return $this;
    }

    /**
     * Get providerkind.
     *
     * @return bool
     */
    public function getProviderkind()
    {
        return $this->providerkind;
    }

    /**
     * Set internalnote.
     *
     * @param string $internalnote
     * @return Socialad
     */
    public function setInternalnote($internalnote)
    {
        $this->internalnote = $internalnote;

        return $this;
    }

    /**
     * Get internalnote.
     *
     * @return string
     */
    public function getInternalnote()
    {
        return $this->internalnote;
    }
}
