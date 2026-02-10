<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trade.
 *
 * @ORM\Table(name="Trade")
 * @ORM\Entity
 */
class Trade
{
    /**
     * @var int
     * @ORM\Column(name="TradeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $tradeid;

    /**
     * @var int
     * @ORM\Column(name="MilesToTrade", type="integer", nullable=false)
     */
    protected $milestotrade;

    /**
     * @var string
     * @ORM\Column(name="AwardProgramPassword", type="string", length=40, nullable=true)
     */
    protected $awardprogrampassword;

    /**
     * @var int
     * @ORM\Column(name="ReceiveMoneyVia", type="integer", nullable=false)
     */
    protected $receivemoneyvia;

    /**
     * @var string
     * @ORM\Column(name="PayPalEmail", type="string", length=40, nullable=true)
     */
    protected $paypalemail;

    /**
     * @var string
     * @ORM\Column(name="ShippingAddress1", type="string", length=250, nullable=true)
     */
    protected $shippingaddress1;

    /**
     * @var string
     * @ORM\Column(name="ShippingAddress2", type="string", length=250, nullable=true)
     */
    protected $shippingaddress2;

    /**
     * @var string
     * @ORM\Column(name="ShippingCity", type="string", length=80, nullable=true)
     */
    protected $shippingcity;

    /**
     * @var string
     * @ORM\Column(name="ShippingZip", type="string", length=40, nullable=true)
     */
    protected $shippingzip;

    /**
     * @var string
     * @ORM\Column(name="ShippingFirstName", type="string", length=40, nullable=true)
     */
    protected $shippingfirstname;

    /**
     * @var string
     * @ORM\Column(name="ShippingLastName", type="string", length=40, nullable=true)
     */
    protected $shippinglastname;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $createdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="PostDate", type="datetime", nullable=true)
     */
    protected $postdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="PaymentDate", type="datetime", nullable=true)
     */
    protected $paymentdate;

    /**
     * @var \DateTime
     * @ORM\Column(name="SaleDate", type="datetime", nullable=true)
     */
    protected $saledate;

    /**
     * @var string
     * @ORM\Column(name="MerchandiseLink", type="string", length=250, nullable=true)
     */
    protected $merchandiselink;

    /**
     * @var float
     * @ORM\Column(name="SaleFees", type="decimal", nullable=true)
     */
    protected $salefees;

    /**
     * @var float
     * @ORM\Column(name="SalePrice", type="decimal", nullable=true)
     */
    protected $saleprice;

    /**
     * @var float
     * @ORM\Column(name="AmountSent", type="decimal", nullable=true)
     */
    protected $amountsent;

    /**
     * @var \Country
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShippingCountryID", referencedColumnName="CountryID")
     * })
     */
    protected $shippingcountryid;

    /**
     * @var \State
     * @ORM\ManyToOne(targetEntity="State")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ShippingStateID", referencedColumnName="StateID")
     * })
     */
    protected $shippingstateid;

    /**
     * @var \Account
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="AccountID", referencedColumnName="AccountID")
     * })
     */
    protected $accountid;

    /**
     * Get tradeid.
     *
     * @return int
     */
    public function getTradeid()
    {
        return $this->tradeid;
    }

    /**
     * Set milestotrade.
     *
     * @param int $milestotrade
     * @return Trade
     */
    public function setMilestotrade($milestotrade)
    {
        $this->milestotrade = $milestotrade;

        return $this;
    }

    /**
     * Get milestotrade.
     *
     * @return int
     */
    public function getMilestotrade()
    {
        return $this->milestotrade;
    }

    /**
     * Set awardprogrampassword.
     *
     * @param string $awardprogrampassword
     * @return Trade
     */
    public function setAwardprogrampassword($awardprogrampassword)
    {
        $this->awardprogrampassword = $awardprogrampassword;

        return $this;
    }

    /**
     * Get awardprogrampassword.
     *
     * @return string
     */
    public function getAwardprogrampassword()
    {
        return $this->awardprogrampassword;
    }

    /**
     * Set receivemoneyvia.
     *
     * @param int $receivemoneyvia
     * @return Trade
     */
    public function setReceivemoneyvia($receivemoneyvia)
    {
        $this->receivemoneyvia = $receivemoneyvia;

        return $this;
    }

    /**
     * Get receivemoneyvia.
     *
     * @return int
     */
    public function getReceivemoneyvia()
    {
        return $this->receivemoneyvia;
    }

    /**
     * Set paypalemail.
     *
     * @param string $paypalemail
     * @return Trade
     */
    public function setPaypalemail($paypalemail)
    {
        $this->paypalemail = $paypalemail;

        return $this;
    }

    /**
     * Get paypalemail.
     *
     * @return string
     */
    public function getPaypalemail()
    {
        return $this->paypalemail;
    }

    /**
     * Set shippingaddress1.
     *
     * @param string $shippingaddress1
     * @return Trade
     */
    public function setShippingaddress1($shippingaddress1)
    {
        $this->shippingaddress1 = $shippingaddress1;

        return $this;
    }

    /**
     * Get shippingaddress1.
     *
     * @return string
     */
    public function getShippingaddress1()
    {
        return $this->shippingaddress1;
    }

    /**
     * Set shippingaddress2.
     *
     * @param string $shippingaddress2
     * @return Trade
     */
    public function setShippingaddress2($shippingaddress2)
    {
        $this->shippingaddress2 = $shippingaddress2;

        return $this;
    }

    /**
     * Get shippingaddress2.
     *
     * @return string
     */
    public function getShippingaddress2()
    {
        return $this->shippingaddress2;
    }

    /**
     * Set shippingcity.
     *
     * @param string $shippingcity
     * @return Trade
     */
    public function setShippingcity($shippingcity)
    {
        $this->shippingcity = $shippingcity;

        return $this;
    }

    /**
     * Get shippingcity.
     *
     * @return string
     */
    public function getShippingcity()
    {
        return $this->shippingcity;
    }

    /**
     * Set shippingzip.
     *
     * @param string $shippingzip
     * @return Trade
     */
    public function setShippingzip($shippingzip)
    {
        $this->shippingzip = $shippingzip;

        return $this;
    }

    /**
     * Get shippingzip.
     *
     * @return string
     */
    public function getShippingzip()
    {
        return $this->shippingzip;
    }

    /**
     * Set shippingfirstname.
     *
     * @param string $shippingfirstname
     * @return Trade
     */
    public function setShippingfirstname($shippingfirstname)
    {
        $this->shippingfirstname = $shippingfirstname;

        return $this;
    }

    /**
     * Get shippingfirstname.
     *
     * @return string
     */
    public function getShippingfirstname()
    {
        return $this->shippingfirstname;
    }

    /**
     * Set shippinglastname.
     *
     * @param string $shippinglastname
     * @return Trade
     */
    public function setShippinglastname($shippinglastname)
    {
        $this->shippinglastname = $shippinglastname;

        return $this;
    }

    /**
     * Get shippinglastname.
     *
     * @return string
     */
    public function getShippinglastname()
    {
        return $this->shippinglastname;
    }

    /**
     * Set createdate.
     *
     * @param \DateTime $createdate
     * @return Trade
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
     * Set postdate.
     *
     * @param \DateTime $postdate
     * @return Trade
     */
    public function setPostdate($postdate)
    {
        $this->postdate = $postdate;

        return $this;
    }

    /**
     * Get postdate.
     *
     * @return \DateTime
     */
    public function getPostdate()
    {
        return $this->postdate;
    }

    /**
     * Set paymentdate.
     *
     * @param \DateTime $paymentdate
     * @return Trade
     */
    public function setPaymentdate($paymentdate)
    {
        $this->paymentdate = $paymentdate;

        return $this;
    }

    /**
     * Get paymentdate.
     *
     * @return \DateTime
     */
    public function getPaymentdate()
    {
        return $this->paymentdate;
    }

    /**
     * Set saledate.
     *
     * @param \DateTime $saledate
     * @return Trade
     */
    public function setSaledate($saledate)
    {
        $this->saledate = $saledate;

        return $this;
    }

    /**
     * Get saledate.
     *
     * @return \DateTime
     */
    public function getSaledate()
    {
        return $this->saledate;
    }

    /**
     * Set merchandiselink.
     *
     * @param string $merchandiselink
     * @return Trade
     */
    public function setMerchandiselink($merchandiselink)
    {
        $this->merchandiselink = $merchandiselink;

        return $this;
    }

    /**
     * Get merchandiselink.
     *
     * @return string
     */
    public function getMerchandiselink()
    {
        return $this->merchandiselink;
    }

    /**
     * Set salefees.
     *
     * @param float $salefees
     * @return Trade
     */
    public function setSalefees($salefees)
    {
        $this->salefees = $salefees;

        return $this;
    }

    /**
     * Get salefees.
     *
     * @return float
     */
    public function getSalefees()
    {
        return $this->salefees;
    }

    /**
     * Set saleprice.
     *
     * @param float $saleprice
     * @return Trade
     */
    public function setSaleprice($saleprice)
    {
        $this->saleprice = $saleprice;

        return $this;
    }

    /**
     * Get saleprice.
     *
     * @return float
     */
    public function getSaleprice()
    {
        return $this->saleprice;
    }

    /**
     * Set amountsent.
     *
     * @param float $amountsent
     * @return Trade
     */
    public function setAmountsent($amountsent)
    {
        $this->amountsent = $amountsent;

        return $this;
    }

    /**
     * Get amountsent.
     *
     * @return float
     */
    public function getAmountsent()
    {
        return $this->amountsent;
    }

    /**
     * Set shippingcountryid.
     *
     * @return Trade
     */
    public function setShippingcountryid(?Country $shippingcountryid = null)
    {
        $this->shippingcountryid = $shippingcountryid;

        return $this;
    }

    /**
     * Get shippingcountryid.
     *
     * @return \AwardWallet\MainBundle\Entity\Country
     */
    public function getShippingcountryid()
    {
        return $this->shippingcountryid;
    }

    /**
     * Set shippingstateid.
     *
     * @return Trade
     */
    public function setShippingstateid(?State $shippingstateid = null)
    {
        $this->shippingstateid = $shippingstateid;

        return $this;
    }

    /**
     * Get shippingstateid.
     *
     * @return \AwardWallet\MainBundle\Entity\State
     */
    public function getShippingstateid()
    {
        return $this->shippingstateid;
    }

    /**
     * Set accountid.
     *
     * @return Trade
     */
    public function setAccountid(?Account $accountid = null)
    {
        $this->accountid = $accountid;

        return $this;
    }

    /**
     * Get accountid.
     *
     * @return \AwardWallet\MainBundle\Entity\Account
     */
    public function getAccountid()
    {
        return $this->accountid;
    }
}
