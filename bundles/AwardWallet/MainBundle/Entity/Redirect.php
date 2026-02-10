<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Redirect.
 *
 * @ORM\Table(name="Redirect")
 * @ORM\Entity
 */
class Redirect
{
    /**
     * @var int
     * @ORM\Column(name="RedirectID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $redirectid;

    /**
     * @var string
     * @ORM\Column(name="URL", type="string", length=1000, nullable=false)
     */
    protected $url;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=128, nullable=false)
     */
    protected $name;

    /**
     * Get redirectid.
     *
     * @return int
     */
    public function getRedirectid()
    {
        return $this->redirectid;
    }

    /**
     * Set url.
     *
     * @param string $url
     * @return Redirect
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
     * Set name.
     *
     * @param string $name
     * @return Redirect
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
}
