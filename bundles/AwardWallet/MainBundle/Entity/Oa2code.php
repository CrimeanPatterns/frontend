<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Oa2code.
 *
 * @ORM\Table(name="OA2Code")
 * @ORM\Entity
 */
class Oa2code
{
    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=40, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $code;

    /**
     * @var string
     * @ORM\Column(name="RedirectURL", type="string", length=200, nullable=false)
     */
    protected $redirecturl;

    /**
     * @var \DateTime
     * @ORM\Column(name="Expires", type="datetime", nullable=false)
     */
    protected $expires;

    /**
     * @var string
     * @ORM\Column(name="Scope", type="string", length=250, nullable=true)
     */
    protected $scope;

    /**
     * @var \Oa2client
     * @ORM\ManyToOne(targetEntity="Oa2client")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="OA2ClientID", referencedColumnName="OA2ClientID")
     * })
     */
    protected $oa2clientid;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set redirecturl.
     *
     * @param string $redirecturl
     * @return Oa2code
     */
    public function setRedirecturl($redirecturl)
    {
        $this->redirecturl = $redirecturl;

        return $this;
    }

    /**
     * Get redirecturl.
     *
     * @return string
     */
    public function getRedirecturl()
    {
        return $this->redirecturl;
    }

    /**
     * Set expires.
     *
     * @param \DateTime $expires
     * @return Oa2code
     */
    public function setExpires($expires)
    {
        $this->expires = $expires;

        return $this;
    }

    /**
     * Get expires.
     *
     * @return \DateTime
     */
    public function getExpires()
    {
        return $this->expires;
    }

    /**
     * Set scope.
     *
     * @param string $scope
     * @return Oa2code
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * Get scope.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set oa2clientid.
     *
     * @return Oa2code
     */
    public function setOa2clientid(?Oa2client $oa2clientid = null)
    {
        $this->oa2clientid = $oa2clientid;

        return $this;
    }

    /**
     * Get oa2clientid.
     *
     * @return \AwardWallet\MainBundle\Entity\Oa2client
     */
    public function getOa2clientid()
    {
        return $this->oa2clientid;
    }

    /**
     * Set userid.
     *
     * @return Oa2code
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
