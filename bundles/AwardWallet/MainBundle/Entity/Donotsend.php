<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Donotsend.
 *
 * @ORM\Table(name="DoNotSend")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\DonotsendRepository")
 */
class Donotsend
{
    /**
     * @var int
     * @ORM\Column(name="DoNotSendID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $donotsendid;

    /**
     * @var string
     * @ORM\Column(name="Email", type="text", nullable=false)
     */
    protected $email;

    /**
     * @var \DateTime
     * @ORM\Column(name="AddTime", type="datetime", nullable=false)
     */
    protected $addtime;

    /**
     * @var string
     * @ORM\Column(name="IP", type="text", nullable=false)
     */
    protected $ip;

    public function __construct($email, $ip)
    {
        $this->setEmail($email);
        $this->setIp($ip);
        $this->setAddtime(new \DateTime());
    }

    /**
     * Get donotsendid.
     *
     * @return int
     */
    public function getDonotsendid()
    {
        return $this->donotsendid;
    }

    /**
     * Set email.
     *
     * @param string $email
     * @return Donotsend
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set addtime.
     *
     * @param \DateTime $addtime
     * @return Donotsend
     */
    public function setAddtime($addtime)
    {
        $this->addtime = $addtime;

        return $this;
    }

    /**
     * Get addtime.
     *
     * @return \DateTime
     */
    public function getAddtime()
    {
        return $this->addtime;
    }

    /**
     * Set ip.
     *
     * @param string $ip
     * @return Donotsend
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip.
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
}
