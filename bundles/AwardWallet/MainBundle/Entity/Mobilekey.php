<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mobilekey.
 *
 * @ORM\Table(name="MobileKey")
 * @ORM\Entity
 */
class Mobilekey
{
    /**
     * @var int
     * @ORM\Column(name="MobileKeyID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $mobilekeyid;

    /**
     * @var string
     * @ORM\Column(name="MobileKey", type="string", length=20, nullable=false)
     */
    protected $mobilekey;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $createdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="AskPasswordDate", type="datetime", nullable=false)
     */
    protected $askpassworddate;

    /**
     * @var int
     * @ORM\Column(name="Kind", type="integer", nullable=false)
     */
    protected $kind = 1;

    /**
     * @var string
     * @ORM\Column(name="Params", type="text", nullable=true)
     */
    protected $params;

    /**
     * @var string
     * @ORM\Column(name="UsedBy", type="string", length=20, nullable=true)
     */
    protected $usedby;

    /**
     * @var bool
     * @ORM\Column(name="AccessLevel", type="boolean", nullable=true)
     */
    protected $accesslevel;

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $state = 1;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get mobilekeyid.
     *
     * @return int
     */
    public function getMobilekeyid()
    {
        return $this->mobilekeyid;
    }

    /**
     * Set mobilekey.
     *
     * @param string $mobilekey
     * @return Mobilekey
     */
    public function setMobilekey($mobilekey)
    {
        $this->mobilekey = $mobilekey;

        return $this;
    }

    /**
     * Get mobilekey.
     *
     * @return string
     */
    public function getMobilekey()
    {
        return $this->mobilekey;
    }

    /**
     * Set createdate.
     *
     * @param \DateTime $createdate
     * @return Mobilekey
     */
    public function setCreatedate($createdate)
    {
        $this->createdate = $createdate;

        return $this;
    }

    /**
     * Get createdate.
     *
     * @return \DateTime
     */
    public function getCreatedate()
    {
        return $this->createdate;
    }

    /**
     * Set askpassworddate.
     *
     * @param \DateTime $askpassworddate
     * @return Mobilekey
     */
    public function setAskpassworddate($askpassworddate)
    {
        $this->askpassworddate = $askpassworddate;

        return $this;
    }

    /**
     * Get askpassworddate.
     *
     * @return \DateTime
     */
    public function getAskpassworddate()
    {
        return $this->askpassworddate;
    }

    /**
     * Set kind.
     *
     * @param int $kind
     * @return Mobilekey
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
     * Set params.
     *
     * @param string $params
     * @return Mobilekey
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params.
     *
     * @return string
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set usedby.
     *
     * @param string $usedby
     * @return Mobilekey
     */
    public function setUsedby($usedby)
    {
        $this->usedby = $usedby;

        return $this;
    }

    /**
     * Get usedby.
     *
     * @return string
     */
    public function getUsedby()
    {
        return $this->usedby;
    }

    /**
     * Set accesslevel.
     *
     * @param bool $accesslevel
     * @return Mobilekey
     */
    public function setAccesslevel($accesslevel)
    {
        $this->accesslevel = $accesslevel;

        return $this;
    }

    /**
     * Get accesslevel.
     *
     * @return bool
     */
    public function getAccesslevel()
    {
        return $this->accesslevel;
    }

    /**
     * Set state.
     *
     * @param int $state
     * @return Mobilekey
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state.
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set userid.
     *
     * @return Mobilekey
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
