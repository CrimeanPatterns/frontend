<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MobileDevice.
 *
 * @ORM\Table(name="MobileDevice")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\MobileDeviceRepository")
 */
class MobileDevice
{
    public const TYPE_ANDROID = 1;
    public const TYPE_IOS = 2;
    public const TYPE_SAFARI = 3;
    public const TYPE_CHROME = 4;
    public const TYPE_FIREFOX = 5;
    public const TYPE_PUSHY_ANDROID = 6;

    public const TYPE_NAMES = [
        self::TYPE_ANDROID => 'android',
        self::TYPE_IOS => 'ios',
        self::TYPE_SAFARI => 'safari',
        self::TYPE_CHROME => 'chrome',
        self::TYPE_FIREFOX => 'firefox',
        self::TYPE_PUSHY_ANDROID => 'pushy-android',
    ];

    public const TYPES_MOBILE = [
        self::TYPE_ANDROID,
        self::TYPE_IOS,
        self::TYPE_PUSHY_ANDROID,
    ];

    public const TYPES_DESKTOP = [
        self::TYPE_SAFARI,
        self::TYPE_CHROME,
        self::TYPE_FIREFOX,
    ];

    public const TYPES_ALL = [
        self::TYPE_ANDROID,
        self::TYPE_IOS,
        self::TYPE_SAFARI,
        self::TYPE_CHROME,
        self::TYPE_FIREFOX,
        self::TYPE_PUSHY_ANDROID,
    ];

    /**
     * @var int
     * @ORM\Column(name="MobileDeviceID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $mobileDeviceId;

    /**
     * @var string
     * @ORM\Column(name="DeviceKey", type="string", nullable=false)
     */
    protected $deviceKey;

    /**
     * @var int
     * @ORM\Column(name="DeviceType", type="integer", nullable=false)
     */
    protected $deviceType;

    /**
     * @var string
     * @ORM\Column(name="Lang", type="string", nullable=false)
     */
    protected $lang;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userId;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected $updateDate;

    /**
     * @var string
     * @ORM\Column(name="AppVersion", type="string", nullable=true)
     */
    protected $appVersion;

    /**
     * @var string
     * @ORM\Column(name="IP", type="string", nullable=true)
     */
    protected $ip;

    /**
     * @var string
     * @ORM\Column(name="Alias", type="string", nullable=true)
     */
    protected $alias;

    /**
     * @var ?string
     * @ORM\Column(name="Secret", type="string", nullable=true)
     */
    protected $secret;

    /**
     * @var ?Remembermetoken
     * @ORM\ManyToOne(targetEntity="Remembermetoken")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="RememberMeTokenID", referencedColumnName="RememberMeTokenID")
     * })
     */
    protected $rememberMeToken;

    /**
     * @var bool
     * @ORM\Column(name="Tracked", type="boolean", nullable=false)
     */
    protected $tracked;

    /**
     * @ORM\Column(name="UserAgent", type="string", nullable=true)
     */
    protected ?string $useragent = null;

    /**
     * MobileDevice constructor.
     */
    public function __construct()
    {
        $this->creationDate = new \DateTime();
        $this->updateDate = new \DateTime();
        $this->tracked = true;
    }

    /**
     * @return string
     */
    public static function getTypeName($type)
    {
        return self::TYPE_NAMES[$type];
    }

    /**
     * @param string $typeName
     * @return int
     */
    public static function getTypeId($typeName)
    {
        $result = array_search($typeName, self::TYPE_NAMES);

        if ($result === false) {
            throw new \InvalidArgumentException('Unknown device type: ' . $typeName);
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getMobileDeviceId()
    {
        return $this->mobileDeviceId;
    }

    /**
     * @return string
     */
    public function getDeviceKey()
    {
        return $this->deviceKey;
    }

    /**
     * @param string $deviceKey
     * @return $this
     */
    public function setDeviceKey($deviceKey)
    {
        $this->deviceKey = $deviceKey;

        return $this;
    }

    /**
     * @return int
     */
    public function getDeviceType()
    {
        return $this->deviceType;
    }

    /**
     * @param int $deviceType
     * @return $this
     */
    public function setDeviceType($deviceType)
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    public function getUser(): ?Usr
    {
        return $this->userId;
    }

    public function setUser(?Usr $userId): self
    {
        if ($userId === $this->userId) {
            return $this;
        }
        $this->userId = $userId;

        if ($userId) {
            $userId->addDevice($this);
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param \DateTime $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * @return MobileDevice
     */
    public function setUpdateDate(\DateTime $updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     * @return $this
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * @return string
     */
    public function getAppVersion()
    {
        return $this->appVersion;
    }

    /**
     * @param string $appVersion
     * @return $this
     */
    public function setAppVersion($appVersion)
    {
        $this->appVersion = $appVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return $this
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMobile()
    {
        return in_array($this->getDeviceType(), self::TYPES_MOBILE);
    }

    /**
     * @return bool
     */
    public function isDesktop()
    {
        return in_array($this->getDeviceType(), self::TYPES_DESKTOP);
    }

    public function getName()
    {
        return self::getTypeName($this->getDeviceType());
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function getUserId(): Usr
    {
        return $this->userId;
    }

    public function setUserId(Usr $userId): MobileDevice
    {
        $this->userId = $userId;

        return $this;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function setSecret(?string $secret): MobileDevice
    {
        $this->secret = $secret;

        return $this;
    }

    public function hasSecret(): bool
    {
        return null !== $this->secret;
    }

    public function getRememberMeToken(): ?Remembermetoken
    {
        return $this->rememberMeToken;
    }

    public function setRememberMeToken(?Remembermetoken $rememberMeToken): MobileDevice
    {
        $this->rememberMeToken = $rememberMeToken;

        return $this;
    }

    public function isTracked(): bool
    {
        return $this->tracked;
    }

    public function setTracked(bool $tracked): MobileDevice
    {
        $this->tracked = $tracked;

        return $this;
    }

    public function getUseragent(): ?string
    {
        return $this->useragent;
    }

    public function setUseragent(?string $useragent): self
    {
        $this->useragent = $useragent;

        return $this;
    }
}
