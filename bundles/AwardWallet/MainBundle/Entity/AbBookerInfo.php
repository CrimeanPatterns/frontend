<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * AwardWallet\MainBundle\Entity\AbBookerInfo.
 *
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\AbBookerInfoRepository")
 * @ORM\Table(
 *     name="AbBookerInfo",
 *     indexes={
 * @ORM\Index(name="fk1_user", columns={"UserID"}),
 * @ORM\Index(name="SiteAdIDKey", columns={"SiteAdID"})
 *     }
 * )
 */
class AbBookerInfo
{
    public const IMAGE_PATH = 'assets/awardwalletmain/images/booking';

    public const REQUEST_COST = 20;

    public const AUTOREPLY_INVOICE_BOOKERS = [
        // AwardMagic
        [
            'booker_id' => 327644,
            'description' => 'invoice.item.award_magic_initial_deposit',
            'price' => 25,
            'quantity' => 1,
        ],
    ];

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $AbBookerInfoID;

    /**
     * @var float
     * @ORM\Column(type="decimal", length=10, nullable=false, scale=2)
     * @Assert\NotBlank()
     */
    protected $Price;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     * @Assert\Length(max = "4000")
     */
    protected $PricingDetails;

    /**
     * @var string
     * @ORM\Column(type="string", length=120, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "120", allowEmptyString="true")
     */
    protected $ServiceName;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "2", max = "10", allowEmptyString="true")
     */
    protected $ServiceShortName;

    /**
     * @var string
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(max = "20")
     */
    protected $MerchantName;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(min = "3", max = "255", allowEmptyString="true")
     */
    protected $Address;

    /**
     * @var string
     * @ORM\Column(type="string", length=250, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Url()
     * @Assert\Length(max = "250")
     */
    protected $ServiceURL;

    /**
     * @var float
     * @ORM\Column(type="decimal", length=10, nullable=false, scale=2)
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThan(value = 0)
     * @Assert\LessThanOrEqual(value = 100)
     */
    protected $OutboundPercent;

    /**
     * @var float
     * @ORM\Column(type="decimal", length=10, nullable=false, scale=2)
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThan(value = 0)
     * @Assert\LessThanOrEqual(value = 100)
     */
    protected $InboundPercent;

    /**
     * @var int
     * @ORM\Column(name="Discount", type="integer", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Type(type="numeric")
     * @Assert\GreaterThan(value = 0)
     * @Assert\LessThanOrEqual(value = 100)
     */
    protected $discount = 0;

    /**
     * @var string
     * @ORM\Column(type="string", length=250, nullable=true)
     * @Assert\Url()
     * @Assert\Length(max = "250")
     */
    protected $SmtpServer;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Type(type="integer")
     */
    protected $SmtpPort;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=true)
     * @Assert\Type(type="bool")
     */
    protected $SmtpUseSsl;

    /**
     * @var string
     * @ORM\Column(type="string", length=250, nullable=true)
     * @Assert\Length(min = "1", max = "250", allowEmptyString="true")
     */
    protected $SmtpUsername;

    /**
     * @var string
     * @ORM\Column(type="string", length=250, nullable=true)
     * @Assert\Length(min = "1", max = "250", allowEmptyString="true")
     */
    protected $SmtpPassword;

    /**
     * @var string
     * @ORM\Column(type="string", length=250, nullable=true)
     * @Assert\Length(min = "1", max = "250", allowEmptyString="true")
     */
    protected $SmtpError;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?\DateTime $SmtpErrorDate = null;

    /**
     * will be set to From header in emails.
     *
     * @var string
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\Length(min = "1", max = "80", allowEmptyString="true")
     */
    protected $FromEmail;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max = "8000")
     */
    protected $Greeting;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max = "8000")
     */
    protected $AutoReplyMessage;

    /**
     * @var int
     * @ORM\Column(name="SiteAdID", type="integer", nullable=true)
     */
    protected $SiteAdID;

    /**
     * @var Usr
     * @ORM\OneToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $UserID;

    /**
     * this mailbox used by ScanBookerMailboxCommand to receive replies by email, format: {imap.gmail.com:993/ssl}.
     *
     * @var string
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    protected $ImapMailbox;
    /**
     * @var string
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    protected $ImapLogin;
    /**
     * @var string
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    protected $ImapPassword;

    /**
     * @var Currency
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\Currency")
     * @ORM\JoinColumn(name="CurrencyID", referencedColumnName="CurrencyID", nullable=false)
     */
    protected $Currency;

    /**
     * @var string
     * @ORM\Column(type="string", length=250, nullable=true)
     */
    protected $PayPalPassword;

    /**
     * @var string
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    protected $PayPalClientId;

    /**
     * @var string
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    protected $PayPalSecret;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServeEconomyClass;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServeInternational;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServeDomestic;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServeReservationAir;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServeReservationHotel;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServeReservationCar;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServeReservationCruises;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServePaymentCash;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $ServePaymentMiles;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $RequirePriorSearches;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $RequireCustomer;

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     */
    protected $DisableAd = false;

    /**
     * @var bool
     * @ORM\Column(name="AcceptChecks", type="boolean", nullable=false)
     */
    protected $acceptChecks = false;

    /**
     * default credit card gateway.
     *
     * @var int
     * @ORM\Column(type="integer", name="CreditCardPaymentType", nullable=false)
     */
    protected $creditCardPaymentType = Cart::PAYMENTTYPE_CREDITCARD;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="IncludeCreditCardFee", nullable=false)
     */
    protected $includeCreditCardFee = true;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $updateDate;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="UsCentric", nullable=false)
     */
    protected $usCentric = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="ServePremiumEconomy", nullable=false)
     */
    protected $servePremiumEconomy = false;

    /**
     * @var bool
     * @ORM\Column(type="boolean", name="AllowBusinessOrPersonalSelect", nullable=false)
     */
    protected $allowBusinessOrPersonalSelect = false;

    /**
     * Get AbBookerInfoID.
     *
     * @return int
     */
    public function getAbBookerInfoID()
    {
        return $this->AbBookerInfoID;
    }

    /**
     * Set Price.
     *
     * @param float $Price
     * @return AbBookerInfo
     */
    public function setPrice($Price)
    {
        $this->Price = $Price;

        return $this;
    }

    /**
     * Get Price.
     *
     * @return float
     */
    public function getPrice()
    {
        return $this->Price;
    }

    /**
     * Set PricingDetails.
     *
     * @param string $PricingDetails
     * @return AbBookerInfo
     */
    public function setPricingDetails($PricingDetails)
    {
        $this->PricingDetails = $PricingDetails;

        return $this;
    }

    /**
     * Get PricingDetails.
     *
     * @return string
     */
    public function getPricingDetails()
    {
        return $this->PricingDetails;
    }

    /**
     * Set ServiceName.
     *
     * @param string $ServiceName
     * @return AbBookerInfo
     */
    public function setServiceName($ServiceName)
    {
        $this->ServiceName = $ServiceName;

        return $this;
    }

    /**
     * Get ServiceName.
     *
     * @return string
     */
    public function getServiceName()
    {
        return $this->ServiceName;
    }

    /**
     * Set ServiceShortName.
     *
     * @param string $ServiceShortName
     * @return AbBookerInfo
     */
    public function setServiceShortName($ServiceShortName)
    {
        $this->ServiceShortName = $ServiceShortName;

        return $this;
    }

    /**
     * Get ServiceShortName.
     *
     * @return string
     */
    public function getServiceShortName()
    {
        return $this->ServiceShortName;
    }

    /**
     * @param string $MerchantName
     */
    public function setMerchantName($MerchantName)
    {
        $this->MerchantName = $MerchantName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMerchantName()
    {
        return $this->MerchantName;
    }

    /**
     * Set Address.
     *
     * @param string $Address
     * @return AbBookerInfo
     */
    public function setAddress($Address)
    {
        $this->Address = $Address;

        return $this;
    }

    /**
     * Get Address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->Address;
    }

    /**
     * Set ServiceURL.
     *
     * @param string $ServiceURL
     * @return AbBookerInfo
     */
    public function setServiceURL($ServiceURL)
    {
        $this->ServiceURL = $ServiceURL;

        return $this;
    }

    /**
     * Get ServiceURL.
     *
     * @return string
     */
    public function getServiceURL()
    {
        return $this->ServiceURL;
    }

    /**
     * Set OutboundPercent.
     *
     * @param float $OutboundPercent
     * @return AbBookerInfo
     */
    public function setOutboundPercent($OutboundPercent)
    {
        $this->OutboundPercent = $OutboundPercent;

        return $this;
    }

    /**
     * Get OutboundPercent.
     *
     * @return float
     */
    public function getOutboundPercent()
    {
        return $this->OutboundPercent;
    }

    /**
     * Set InboundPercent.
     *
     * @param float $InboundPercent
     * @return AbBookerInfo
     */
    public function setInboundPercent($InboundPercent)
    {
        $this->InboundPercent = $InboundPercent;

        return $this;
    }

    /**
     * Get InboundPercent.
     *
     * @return float
     */
    public function getInboundPercent()
    {
        return $this->InboundPercent;
    }

    /**
     * @return int
     */
    public function getDiscount()
    {
        if ($this->getUserID()->getBusinessInfo()->isTrial()) {
            return 100;
        }

        return $this->discount;
    }

    /**
     * @param int $discount
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Set SmtpServer.
     *
     * @param string $SmtpServer
     * @return AbBookerInfo
     */
    public function setSmtpServer($SmtpServer)
    {
        $this->SmtpServer = $SmtpServer;

        return $this;
    }

    /**
     * Get SmtpServer.
     *
     * @return string
     */
    public function getSmtpServer()
    {
        return $this->SmtpServer;
    }

    /**
     * Set SmtpPort.
     *
     * @param int $SmtpPort
     * @return AbBookerInfo
     */
    public function setSmtpPort($SmtpPort)
    {
        $this->SmtpPort = $SmtpPort;

        return $this;
    }

    /**
     * Get SmtpPort.
     *
     * @return int
     */
    public function getSmtpPort()
    {
        return $this->SmtpPort;
    }

    /**
     * Set SmtpUseSsl.
     *
     * @param bool $SmtpUseSsl
     * @return AbBookerInfo
     */
    public function setSmtpUseSsl($SmtpUseSsl)
    {
        $this->SmtpUseSsl = $SmtpUseSsl;

        return $this;
    }

    /**
     * Get SmtpUseSsl.
     *
     * @return bool
     */
    public function getSmtpUseSsl()
    {
        return $this->SmtpUseSsl;
    }

    /**
     * Set SmtpUsername.
     *
     * @param string $SmtpUsername
     * @return AbBookerInfo
     */
    public function setSmtpUsername($SmtpUsername)
    {
        $this->SmtpUsername = $SmtpUsername;

        return $this;
    }

    /**
     * Get SmtpUsername.
     *
     * @return string
     */
    public function getSmtpUsername()
    {
        return $this->SmtpUsername;
    }

    /**
     * Set SmtpPassword.
     *
     * @param string $SmtpPassword
     * @return AbBookerInfo
     */
    public function setSmtpPassword($SmtpPassword)
    {
        $this->SmtpPassword = $SmtpPassword;

        return $this;
    }

    /**
     * Get SmtpPassword.
     *
     * @return string
     */
    public function getSmtpPassword()
    {
        return $this->SmtpPassword;
    }

    /**
     * Set SmtpError.
     *
     * @param string $SmtpError
     * @return AbBookerInfo
     */
    public function setSmtpError($SmtpError)
    {
        $this->SmtpError = $SmtpError;

        return $this;
    }

    /**
     * Get SmtpError.
     *
     * @return string
     */
    public function getSmtpError()
    {
        return $this->SmtpError;
    }

    public function setSmtpErrorDate(?\DateTime $SmtpErrorDate): self
    {
        $this->SmtpErrorDate = $SmtpErrorDate;

        return $this;
    }

    public function getSmtpErrorDate(): ?\DateTime
    {
        return $this->SmtpErrorDate;
    }

    /**
     * Set Greeting.
     *
     * @param string $Greeting
     * @return AbBookerInfo
     */
    public function setGreeting($Greeting)
    {
        $this->Greeting = $Greeting;

        return $this;
    }

    /**
     * Get Greeting.
     *
     * @return string
     */
    public function getGreeting()
    {
        return $this->Greeting;
    }

    /**
     * Set AutoReplyMessage.
     *
     * @param string $AutoReplyMessage
     * @return AbBookerInfo
     */
    public function setAutoReplyMessage($AutoReplyMessage)
    {
        $this->AutoReplyMessage = $AutoReplyMessage;

        return $this;
    }

    /**
     * Get AutoReplyMessage.
     *
     * @return string
     */
    public function getAutoReplyMessage()
    {
        return $this->AutoReplyMessage;
    }

    /**
     * Set SiteAdID.
     *
     * @param int $SiteAdID
     * @return AbBookerInfo
     */
    public function setSiteAdID($SiteAdID)
    {
        $this->SiteAdID = $SiteAdID;

        return $this;
    }

    /**
     * Get SiteAdID.
     *
     * @return int
     */
    public function getSiteAdID()
    {
        return $this->SiteAdID;
    }

    /**
     * Set UserID.
     *
     * @return AbBookerInfo
     */
    public function setUserID(?Usr $UserID = null)
    {
        $this->UserID = $UserID;

        return $this;
    }

    /**
     * Get UserID.
     *
     * @return \AwardWallet\MainBundle\Entity\Usr
     */
    public function getUserID()
    {
        return $this->UserID;
    }

    /**
     * Get icon.
     *
     * @param string $size "small|medium"
     * @param int|null   $ref
     * @return string
     */
    public function getIcon($size = 'small', $ref = null)
    {
        if ($ref) {
            return self::IMAGE_PATH . "/leads/icon_{$ref}_{$size}.png";
        }

        $ssn = strtolower(trim($this->getServiceShortName()));
        $ssn = (empty($ssn)) ? 'aw' : $ssn;

        return self::IMAGE_PATH . "/" . $ssn . "/icon_{$size}.png";
    }

    /**
     * Get logo.
     *
     * @param string $type
     * @return string
     */
    public function getLogo($type = 'email', $ref = null, $old = false)
    {
        if ($type == 'email') {
            $type = !$old ? 'email_v2' : $type;
        }

        if ($ref) {
            return self::IMAGE_PATH . "/leads/logo_{$ref}_{$type}.png";
        }

        $ssn = strtolower(trim($this->getServiceShortName()));
        $ssn = (empty($ssn)) ? 'aw' : $ssn;

        return self::IMAGE_PATH . "/" . $ssn . "/logo_{$type}.png";
    }

    /**
     * Get email logo.
     *
     * @return string
     */
    public function getEmailLogo($old = false)
    {
        return $this->getLogo("email", null, $old);
    }

    public function getMobileInvoiceLogo()
    {
        return $this->getEmailLogo();
    }

    /**
     * does booker use it's own smtp server.
     *
     * @return bool
     */
    public function hasCustomSmtp()
    {
        return !empty($this->getSmtpServer()) && !empty($this->getSmtpPort());
    }

    /**
     * get booker's own smtp server transport.
     *
     * @return \Swift_SmtpTransport
     */
    public function getCustomSmtpTransport()
    {
        $transport = new \Swift_SmtpTransport(
            $this->getSmtpServer(),
            $this->getSmtpPort(),
            $this->getSmtpUseSsl() ? "ssl" : null
        );
        $transport
            ->setUsername($this->getSmtpUsername())
            ->setPassword($this->getSmtpPassword());

        return $transport;
    }

    /**
     * @param string $FromEmail
     */
    public function setFromEmail($FromEmail)
    {
        $this->FromEmail = $FromEmail;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->FromEmail;
    }

    /**
     * Set ImapMailbox.
     *
     * @param string $imapMailbox
     * @return AbBookerInfo
     */
    public function setImapMailbox($imapMailbox)
    {
        $this->ImapMailbox = $imapMailbox;

        return $this;
    }

    /**
     * Get ImapMailbox.
     *
     * @return string
     */
    public function getImapMailbox()
    {
        return $this->ImapMailbox;
    }

    /**
     * Set ImapLogin.
     *
     * @param string $imapLogin
     * @return AbBookerInfo
     */
    public function setImapLogin($imapLogin)
    {
        $this->ImapLogin = $imapLogin;

        return $this;
    }

    /**
     * Get ImapLogin.
     *
     * @return string
     */
    public function getImapLogin()
    {
        return $this->ImapLogin;
    }

    /**
     * Set ImapPassword.
     *
     * @param string $imapPassword
     * @return AbBookerInfo
     */
    public function setImapPassword($imapPassword)
    {
        $this->ImapPassword = $imapPassword;

        return $this;
    }

    /**
     * Get ImapPassword.
     *
     * @return string
     */
    public function getImapPassword()
    {
        return $this->ImapPassword;
    }

    public function getTemplates()
    {
        $ssn = strtolower(trim($this->getServiceShortName()));

        if ($ssn) {
            return [
                '/assets/awardwalletmain/js/booking/' . strtolower($this->getServiceShortName()) . '/templates.js?v=' . FILE_VERSION,
            ];
        } else {
            return [];
        }
    }

    /**
     * @return \AwardWallet\MainBundle\Entity\Currency
     */
    public function getCurrency()
    {
        return $this->Currency;
    }

    /**
     * @param \AwardWallet\MainBundle\Entity\Currency $Currency
     */
    public function setCurrency($Currency)
    {
        $this->Currency = $Currency;
    }

    /**
     * @param string $PayPalPassword
     */
    public function setPayPalPassword($PayPalPassword)
    {
        $this->PayPalPassword = $PayPalPassword;
    }

    /**
     * @return string
     */
    public function getPayPalPassword()
    {
        return $this->PayPalPassword;
    }

    /**
     * @return bool
     */
    public function getServeEconomyClass()
    {
        return $this->ServeEconomyClass;
    }

    /**
     * @param bool $ServeEconomyClass
     */
    public function setServeEconomyClass($ServeEconomyClass)
    {
        $this->ServeEconomyClass = $ServeEconomyClass;
    }

    /**
     * @return bool
     */
    public function getServeInternational()
    {
        return $this->ServeInternational;
    }

    /**
     * @param bool $ServeInternational
     */
    public function setServeInternational($ServeInternational)
    {
        $this->ServeInternational = $ServeInternational;
    }

    /**
     * @return bool
     */
    public function getServeDomestic()
    {
        return $this->ServeDomestic;
    }

    /**
     * @param bool $ServeDomestic
     */
    public function setServeDomestic($ServeDomestic)
    {
        $this->ServeDomestic = $ServeDomestic;
    }

    /**
     * @return bool
     */
    public function getServeReservationAir()
    {
        return $this->ServeReservationAir;
    }

    /**
     * @param bool $ServeReservationAir
     */
    public function setServeReservationAir($ServeReservationAir)
    {
        $this->ServeReservationAir = $ServeReservationAir;
    }

    /**
     * @return bool
     */
    public function getServeReservationHotel()
    {
        return $this->ServeReservationHotel;
    }

    /**
     * @param bool $ServeReservationHotel
     */
    public function setServeReservationHotel($ServeReservationHotel)
    {
        $this->ServeReservationHotel = $ServeReservationHotel;
    }

    /**
     * @return bool
     */
    public function getServeReservationCar()
    {
        return $this->ServeReservationCar;
    }

    /**
     * @param bool $ServeReservationCar
     */
    public function setServeReservationCar($ServeReservationCar)
    {
        $this->ServeReservationCar = $ServeReservationCar;
    }

    /**
     * @return bool
     */
    public function getServeReservationCruises()
    {
        return $this->ServeReservationCruises;
    }

    /**
     * @param bool $ServeReservationCruises
     */
    public function setServeReservationCruises($ServeReservationCruises)
    {
        $this->ServeReservationCruises = $ServeReservationCruises;
    }

    /**
     * @return bool
     */
    public function getServePaymentCash()
    {
        return $this->ServePaymentCash;
    }

    /**
     * @param bool $ServePaymentCash
     */
    public function setServePaymentCash($ServePaymentCash)
    {
        $this->ServePaymentCash = $ServePaymentCash;
    }

    /**
     * @return bool
     */
    public function getServePaymentMiles()
    {
        return $this->ServePaymentMiles;
    }

    /**
     * @param bool $ServePaymentMiles
     */
    public function setServePaymentMiles($ServePaymentMiles)
    {
        $this->ServePaymentMiles = $ServePaymentMiles;
    }

    /**
     * @return bool
     */
    public function getRequirePriorSearches()
    {
        return $this->RequirePriorSearches;
    }

    /**
     * @param bool $RequirePriorSearches
     */
    public function setRequirePriorSearches($RequirePriorSearches)
    {
        $this->RequirePriorSearches = $RequirePriorSearches;
    }

    /**
     * @return bool
     */
    public function getRequireCustomer()
    {
        return $this->RequireCustomer;
    }

    /**
     * @param bool $RequireCustomer
     */
    public function setRequireCustomer($RequireCustomer)
    {
        $this->RequireCustomer = $RequireCustomer;
    }

    /**
     * @return bool
     */
    public function getDisableAd()
    {
        return $this->DisableAd;
    }

    /**
     * @param bool $DisableAd
     */
    public function setDisableAd($DisableAd)
    {
        $this->DisableAd = $DisableAd;
    }

    /**
     * @return bool
     */
    public function getAcceptChecks()
    {
        return $this->acceptChecks;
    }

    /**
     * @param bool $acceptChecks
     */
    public function setAcceptChecks($acceptChecks)
    {
        $this->acceptChecks = $acceptChecks;
    }

    /**
     * credit card payment type for booker.
     *
     * @return int
     */
    public function getCreditCardPaymentType()
    {
        return $this->creditCardPaymentType;
    }

    /**
     * @return bool
     */
    public function isIncludeCreditCardFee()
    {
        return $this->includeCreditCardFee;
    }

    /**
     * @param bool $includeCreditCardFee
     */
    public function setIncludeCreditCardFee($includeCreditCardFee)
    {
        $this->includeCreditCardFee = $includeCreditCardFee;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * @param \DateTime $updateDate
     * @return AbBookerInfo
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getBookerRequestCost()
    {
        return round((100 - $this->getDiscount()) / 100 * self::REQUEST_COST, 2);
    }

    public function isPaid()
    {
        if ($this->getBookerRequestCost() <= 0) {
            return true;
        }

        if ($this->getUserID()->getBusinessInfo()->getBalance() > $this->getBookerRequestCost()) {
            return true;
        }

        return false;
    }

    public function isBlocked()
    {
        return !$this->isPaid();
    }

    /**
     * @param string $PayPalClientId
     * @return AbBookerInfo
     */
    public function setPayPalClientId($PayPalClientId)
    {
        $this->PayPalClientId = $PayPalClientId;

        return $this;
    }

    /**
     * @return string
     */
    public function getPayPalSecret()
    {
        return $this->PayPalSecret;
    }

    /**
     * @return string
     */
    public function getPayPalClientId()
    {
        return $this->PayPalClientId;
    }

    /**
     * @param string $PayPalSecret
     * @return AbBookerInfo
     */
    public function setPayPalSecret($PayPalSecret)
    {
        $this->PayPalSecret = $PayPalSecret;

        return $this;
    }

    public function isAutoreplyInvoiceRequired(): bool
    {
        return (bool) $this->getAutoreplyInvoiceOptions();
    }

    public function getAutoreplyInvoiceOptions()
    {
        $bookerId = $this->getUserID()->getUserid();
        $key = array_search($bookerId, array_column(self::AUTOREPLY_INVOICE_BOOKERS, 'booker_id'));

        return $key !== false ? self::AUTOREPLY_INVOICE_BOOKERS[$key] : null;
    }

    public function isUsCentric(): bool
    {
        return $this->usCentric;
    }

    public function setUsCentric(bool $usCentric): void
    {
        $this->usCentric = $usCentric;
    }

    public function isServePremiumEconomy(): bool
    {
        return $this->servePremiumEconomy;
    }

    public function setServePremiumEconomy(bool $servePremiumEconomy): void
    {
        $this->servePremiumEconomy = $servePremiumEconomy;
    }

    public function isAllowBusinessOrPersonalSelect(): bool
    {
        return $this->allowBusinessOrPersonalSelect;
    }

    public function setAllowBusinessOrPersonalSelect(bool $allowBusinessOrPersonalSelect): void
    {
        $this->allowBusinessOrPersonalSelect = $allowBusinessOrPersonalSelect;
    }
}
