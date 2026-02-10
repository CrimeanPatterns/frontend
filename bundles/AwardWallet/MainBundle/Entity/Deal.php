<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Deal.
 *
 * @ORM\Table(name="Deal")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\DealRepository")
 */
class Deal
{
    /**
     * @var int
     * @ORM\Column(name="DealID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $dealid;

    /**
     * @var string
     * @ORM\Column(name="Title", type="string", length=200, nullable=false)
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="Description", type="text", nullable=false)
     */
    protected $description;

    /**
     * @var string
     * @ORM\Column(name="Link", type="string", length=2048, nullable=true)
     */
    protected $link;

    /**
     * @var string
     * @ORM\Column(name="DealsLink", type="string", length=2048, nullable=true)
     */
    protected $dealslink;

    /**
     * @var int
     * @ORM\Column(name="TimesClicked", type="integer", nullable=false)
     */
    protected $timesclicked = 0;

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
     * @var string
     * @ORM\Column(name="ButtonCaption", type="string", length=32, nullable=true)
     */
    protected $buttoncaption;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=true)
     */
    protected $createdate;

    /**
     * @var string
     * @ORM\Column(name="AffiliateLink", type="string", length=2048, nullable=true)
     */
    protected $affiliatelink;

    /**
     * @var string
     * @ORM\Column(name="Source", type="string", length=50, nullable=false)
     */
    protected $source = '';

    /**
     * @var string
     * @ORM\Column(name="SourceID", type="string", length=20, nullable=false)
     */
    protected $sourceid = '';

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $providerid;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AutologinProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $autologinproviderid;

    public function __construct()
    {
        $this->createdate = new \DateTime();
    }

    /**
     * Get dealid.
     *
     * @return int
     */
    public function getDealid()
    {
        return $this->dealid;
    }

    /**
     * Set title.
     *
     * @param string $title
     * @return Deal
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description.
     *
     * @param string $description
     * @return Deal
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set link.
     *
     * @param string $link
     * @return Deal
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link.
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set dealslink.
     *
     * @param string $dealslink
     * @return Deal
     */
    public function setDealslink($dealslink)
    {
        $this->dealslink = $dealslink;

        return $this;
    }

    /**
     * Get dealslink.
     *
     * @return string
     */
    public function getDealslink()
    {
        return $this->dealslink;
    }

    /**
     * Set timesclicked.
     *
     * @param int $timesclicked
     * @return Deal
     */
    public function setTimesclicked($timesclicked)
    {
        $this->timesclicked = $timesclicked;

        return $this;
    }

    /**
     * Get timesclicked.
     *
     * @return int
     */
    public function getTimesclicked()
    {
        return $this->timesclicked;
    }

    /**
     * Set begindate.
     *
     * @param \DateTime $begindate
     * @return Deal
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
     * @return Deal
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
     * Set buttoncaption.
     *
     * @param string $buttoncaption
     * @return Deal
     */
    public function setButtoncaption($buttoncaption)
    {
        $this->buttoncaption = $buttoncaption;

        return $this;
    }

    /**
     * Get buttoncaption.
     *
     * @return string
     */
    public function getButtoncaption()
    {
        return $this->buttoncaption;
    }

    /**
     * Set createdate.
     *
     * @param \DateTime $createdate
     * @return Deal
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
     * Set affiliatelink.
     *
     * @param string $affiliatelink
     * @return Deal
     */
    public function setAffiliatelink($affiliatelink)
    {
        $this->affiliatelink = $affiliatelink;

        return $this;
    }

    /**
     * Get affiliatelink.
     *
     * @return string
     */
    public function getAffiliatelink()
    {
        return $this->affiliatelink;
    }

    /**
     * Set source.
     *
     * @param string $source
     * @return Deal
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set sourceid.
     *
     * @param string $sourceid
     * @return Deal
     */
    public function setSourceid($sourceid)
    {
        $this->sourceid = $sourceid;

        return $this;
    }

    /**
     * Get sourceid.
     *
     * @return string
     */
    public function getSourceid()
    {
        return $this->sourceid;
    }

    /**
     * Set providerid.
     *
     * @return Deal
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

    /**
     * Set autologinproviderid.
     *
     * @return Deal
     */
    public function setAutologinproviderid(?Provider $autologinproviderid = null)
    {
        $this->autologinproviderid = $autologinproviderid;

        return $this;
    }

    /**
     * Get autologinproviderid.
     *
     * @return \AwardWallet\MainBundle\Entity\Provider
     */
    public function getAutologinproviderid()
    {
        return $this->autologinproviderid;
    }
}
