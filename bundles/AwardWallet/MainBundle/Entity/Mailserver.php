<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mailserver.
 *
 * @ORM\Table(name="MailServer")
 * @ORM\Entity
 */
class Mailserver
{
    /**
     * @var int
     * @ORM\Column(name="MailServerID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $mailserverid;

    /**
     * @var string
     * @ORM\Column(name="Domain", type="string", length=64, nullable=false)
     */
    protected $domain;

    /**
     * @var string
     * @ORM\Column(name="Server", type="string", length=64, nullable=false)
     */
    protected $server;

    /**
     * @var int
     * @ORM\Column(name="Port", type="integer", nullable=false)
     */
    protected $port;

    /**
     * @var bool
     * @ORM\Column(name="UseSsl", type="boolean", nullable=true)
     */
    protected $usessl;

    /**
     * @var bool
     * @ORM\Column(name="Protocol", type="boolean", nullable=true)
     */
    protected $protocol;

    /**
     * @var string
     * @ORM\Column(name="MxKeyWords", type="string", length=250, nullable=true)
     */
    protected $mxkeywords;

    /**
     * @var bool
     * @ORM\Column(name="Connected", type="boolean", nullable=true)
     */
    protected $connected;

    /**
     * Get mailserverid.
     *
     * @return int
     */
    public function getMailserverid()
    {
        return $this->mailserverid;
    }

    /**
     * Set domain.
     *
     * @param string $domain
     * @return Mailserver
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set server.
     *
     * @param string $server
     * @return Mailserver
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server.
     *
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set port.
     *
     * @param int $port
     * @return Mailserver
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get port.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set usessl.
     *
     * @param bool $usessl
     * @return Mailserver
     */
    public function setUsessl($usessl)
    {
        $this->usessl = $usessl;

        return $this;
    }

    /**
     * Get usessl.
     *
     * @return bool
     */
    public function getUsessl()
    {
        return $this->usessl;
    }

    /**
     * Set protocol.
     *
     * @param bool $protocol
     * @return Mailserver
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Get protocol.
     *
     * @return bool
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Set mxkeywords.
     *
     * @param string $mxkeywords
     * @return Mailserver
     */
    public function setMxkeywords($mxkeywords)
    {
        $this->mxkeywords = $mxkeywords;

        return $this;
    }

    /**
     * Get mxkeywords.
     *
     * @return string
     */
    public function getMxkeywords()
    {
        return $this->mxkeywords;
    }

    /**
     * Set connected.
     *
     * @param bool $connected
     * @return Mailserver
     */
    public function setConnected($connected)
    {
        $this->connected = $connected;

        return $this;
    }

    /**
     * Get connected.
     *
     * @return bool
     */
    public function getConnected()
    {
        return $this->connected;
    }
}
