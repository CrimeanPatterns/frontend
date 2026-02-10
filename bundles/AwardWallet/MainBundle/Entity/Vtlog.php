<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Vtlog.
 *
 * @ORM\Table(name="VTLog")
 * @ORM\Entity
 */
class Vtlog
{
    /**
     * @var int
     * @ORM\Column(name="VTLogID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $vtlogid;

    /**
     * @var \DateTime
     * @ORM\Column(name="LogDate", type="datetime", nullable=false)
     */
    protected $logdate;

    /**
     * @var string
     * @ORM\Column(name="URL", type="string", length=500, nullable=false)
     */
    protected $url;

    /**
     * @var int
     * @ORM\Column(name="Success", type="integer", nullable=false)
     */
    protected $success;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get vtlogid.
     *
     * @return int
     */
    public function getVtlogid()
    {
        return $this->vtlogid;
    }

    /**
     * Set logdate.
     *
     * @param \DateTime $logdate
     * @return Vtlog
     */
    public function setLogdate($logdate)
    {
        $this->logdate = $logdate;

        return $this;
    }

    /**
     * Get logdate.
     *
     * @return \DateTime
     */
    public function getLogdate()
    {
        return $this->logdate;
    }

    /**
     * Set url.
     *
     * @param string $url
     * @return Vtlog
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set success.
     *
     * @param int $success
     * @return Vtlog
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success.
     *
     * @return int
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Set userid.
     *
     * @return Vtlog
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
