<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Oa2client.
 *
 * @ORM\Table(name="OA2Client")
 * @ORM\Entity
 */
class Oa2client
{
    /**
     * @var int
     * @ORM\Column(name="OA2ClientID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $oa2clientid;

    /**
     * @var string
     * @ORM\Column(name="Login", type="string", length=40, nullable=false)
     */
    protected $login;

    /**
     * @var string
     * @ORM\Column(name="Pass", type="string", length=40, nullable=false)
     */
    protected $pass;

    /**
     * @var string
     * @ORM\Column(name="RedirectURL", type="string", length=200, nullable=false)
     */
    protected $redirecturl;

    /**
     * Get oa2clientid.
     *
     * @return int
     */
    public function getOa2clientid()
    {
        return $this->oa2clientid;
    }

    /**
     * Set login.
     *
     * @param string $login
     * @return Oa2client
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set pass.
     *
     * @param string $pass
     * @return Oa2client
     */
    public function setPass($pass)
    {
        $this->pass = $pass;

        return $this;
    }

    /**
     * Get pass.
     *
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * Set redirecturl.
     *
     * @param string $redirecturl
     * @return Oa2client
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
}
