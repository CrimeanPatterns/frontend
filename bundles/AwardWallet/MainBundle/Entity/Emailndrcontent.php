<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Emailndrcontent.
 *
 * @ORM\Table(name="EmailNDRContent")
 * @ORM\Entity
 */
class Emailndrcontent
{
    /**
     * @var int
     * @ORM\Column(name="EmailNDRContentID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $emailndrcontentid;

    /**
     * @var int
     * @ORM\Column(name="EmailNDRID", type="integer", nullable=false)
     */
    protected $emailndrid;

    /**
     * @var string
     * @ORM\Column(name="Msg", type="string", length=1000, nullable=false)
     */
    protected $msg;

    /**
     * @var string
     * @ORM\Column(name="MessageID", type="string", length=255, nullable=false)
     */
    protected $messageid;

    /**
     * Get emailndrcontentid.
     *
     * @return int
     */
    public function getEmailndrcontentid()
    {
        return $this->emailndrcontentid;
    }

    /**
     * Set emailndrid.
     *
     * @param int $emailndrid
     * @return Emailndrcontent
     */
    public function setEmailndrid($emailndrid)
    {
        $this->emailndrid = $emailndrid;

        return $this;
    }

    /**
     * Get emailndrid.
     *
     * @return int
     */
    public function getEmailndrid()
    {
        return $this->emailndrid;
    }

    /**
     * Set msg.
     *
     * @param string $msg
     * @return Emailndrcontent
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;

        return $this;
    }

    /**
     * Get msg.
     *
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * Set messageid.
     *
     * @param string $messageid
     * @return Emailndrcontent
     */
    public function setMessageid($messageid)
    {
        $this->messageid = $messageid;

        return $this;
    }

    /**
     * Get messageid.
     *
     * @return string
     */
    public function getMessageid()
    {
        return $this->messageid;
    }
}
