<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Providerpage.
 *
 * @ORM\Table(name="ProviderPage")
 * @ORM\Entity
 */
class Providerpage
{
    /**
     * @var int
     * @ORM\Column(name="ProviderPageID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $providerpageid;

    /**
     * @var int
     * @ORM\Column(name="PageType", type="integer", nullable=false)
     */
    protected $pagetype;

    /**
     * @var string
     * @ORM\Column(name="PageName", type="string", length=250, nullable=false)
     */
    protected $pagename;

    /**
     * @var string
     * @ORM\Column(name="PageURL", type="string", length=250, nullable=false)
     */
    protected $pageurl;

    /**
     * @var string
     * @ORM\Column(name="TextToLookFor", type="text", nullable=true)
     */
    protected $texttolookfor;

    /**
     * @var string
     * @ORM\Column(name="Notes", type="text", nullable=true)
     */
    protected $notes;

    /**
     * @var string
     * @ORM\Column(name="CurHTML", type="text", nullable=true)
     */
    protected $curhtml;

    /**
     * @var string
     * @ORM\Column(name="OldHTML", type="text", nullable=true)
     */
    protected $oldhtml;

    /**
     * @var int
     * @ORM\Column(name="Status", type="integer", nullable=false)
     */
    protected $status = 0;

    /**
     * @var string
     * @ORM\Column(name="StartText", type="string", length=250, nullable=true)
     */
    protected $starttext;

    /**
     * @var string
     * @ORM\Column(name="EndText", type="string", length=250, nullable=true)
     */
    protected $endtext;

    /**
     * @var \Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * Get providerpageid.
     *
     * @return int
     */
    public function getProviderpageid()
    {
        return $this->providerpageid;
    }

    /**
     * Set pagetype.
     *
     * @param int $pagetype
     * @return Providerpage
     */
    public function setPagetype($pagetype)
    {
        $this->pagetype = $pagetype;

        return $this;
    }

    /**
     * Get pagetype.
     *
     * @return int
     */
    public function getPagetype()
    {
        return $this->pagetype;
    }

    /**
     * Set pagename.
     *
     * @param string $pagename
     * @return Providerpage
     */
    public function setPagename($pagename)
    {
        $this->pagename = $pagename;

        return $this;
    }

    /**
     * Get pagename.
     *
     * @return string
     */
    public function getPagename()
    {
        return $this->pagename;
    }

    /**
     * Set pageurl.
     *
     * @param string $pageurl
     * @return Providerpage
     */
    public function setPageurl($pageurl)
    {
        $this->pageurl = $pageurl;

        return $this;
    }

    /**
     * Get pageurl.
     *
     * @return string
     */
    public function getPageurl()
    {
        return $this->pageurl;
    }

    /**
     * Set texttolookfor.
     *
     * @param string $texttolookfor
     * @return Providerpage
     */
    public function setTexttolookfor($texttolookfor)
    {
        $this->texttolookfor = $texttolookfor;

        return $this;
    }

    /**
     * Get texttolookfor.
     *
     * @return string
     */
    public function getTexttolookfor()
    {
        return $this->texttolookfor;
    }

    /**
     * Set notes.
     *
     * @param string $notes
     * @return Providerpage
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes.
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set curhtml.
     *
     * @param string $curhtml
     * @return Providerpage
     */
    public function setCurhtml($curhtml)
    {
        $this->curhtml = $curhtml;

        return $this;
    }

    /**
     * Get curhtml.
     *
     * @return string
     */
    public function getCurhtml()
    {
        return $this->curhtml;
    }

    /**
     * Set oldhtml.
     *
     * @param string $oldhtml
     * @return Providerpage
     */
    public function setOldhtml($oldhtml)
    {
        $this->oldhtml = $oldhtml;

        return $this;
    }

    /**
     * Get oldhtml.
     *
     * @return string
     */
    public function getOldhtml()
    {
        return $this->oldhtml;
    }

    /**
     * Set status.
     *
     * @param int $status
     * @return Providerpage
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set starttext.
     *
     * @param string $starttext
     * @return Providerpage
     */
    public function setStarttext($starttext)
    {
        $this->starttext = $starttext;

        return $this;
    }

    /**
     * Get starttext.
     *
     * @return string
     */
    public function getStarttext()
    {
        return $this->starttext;
    }

    /**
     * Set endtext.
     *
     * @param string $endtext
     * @return Providerpage
     */
    public function setEndtext($endtext)
    {
        $this->endtext = $endtext;

        return $this;
    }

    /**
     * Get endtext.
     *
     * @return string
     */
    public function getEndtext()
    {
        return $this->endtext;
    }

    /**
     * Set providerid.
     *
     * @return Providerpage
     */
    public function setProviderid(?Provider $providerid = null)
    {
        $this->providerid = $providerid;

        return $this;
    }

    /**
     * Get providerid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getProviderid()
    {
        return $this->providerid;
    }
}
