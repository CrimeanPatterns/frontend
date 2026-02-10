<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Globals\Image\Image;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Repository\CreditCardRepository")
 * @ORM\Table(name="CreditCard")
 */
class CreditCard
{
    public const NUMBER_ENDING_DEFAULT = "/\d{4}/";

    public const NUMBER_ENDING = [
        Provider::AMEX_ID => "/\d{5}/",
    ];

    public const NUMBER_ENDING_PARAM_FORMAT = "{number_ending}";

    public const AVAILABLE_FORMAT_PARAMS = [self::NUMBER_ENDING_PARAM_FORMAT];

    public const CITI_PRESTIGE_ID = 29;

    public const CASHBACK_TYPE_USD = 1;
    public const CASHBACK_TYPE_POINT = 2;

    public const QS_AFFILIATE_NONE = 0;
    public const QS_AFFILIATE_DIRECT = 1;
    public const QS_AFFILIATE_CARDRATINGS = 3;

    public const PICTURE_FOLDERNAME = 'creditcard';
    public const PICTURE_SIZES = [
        'original' => 9999,
        'medium' => 150,
    ];

    /**
     * @var int
     * @ORM\Column(name="CreditCardID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="ProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $provider;

    /**
     * @var Provider
     * @ORM\ManyToOne(targetEntity="Provider")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="CobrandProviderID", referencedColumnName="ProviderID")
     * })
     */
    protected $cobrandProvider;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="CardFullName", type="string", length=255, nullable=true)
     */
    protected $cardFullName;

    /**
     * @var string
     * @ORM\Column(name="DisplayNameFormat", type="string", length=250, nullable=true)
     */
    protected $displayNameFormat;

    /**
     * @var string
     * @ORM\Column(name="Patterns", type="text", nullable=true)
     */
    protected $patterns;

    /**
     * @var string
     * @ORM\Column(name="HistoryPatterns", type="text", nullable=true)
     */
    protected $historyPatterns;

    /**
     * @var string
     * @ORM\Column(name="CobrandSubAccPatterns", type="text", nullable=true)
     */
    protected $cobrandSubAccPatterns;

    /**
     * @var string
     * @ORM\Column(name="ClickURL", type="text", nullable=true)
     */
    protected $clickURL;

    /**
     * @var string
     * @ORM\Column(name="DirectClickURL", type="text", nullable=true)
     */
    protected $directClickURL;

    /**
     * @var int
     * @ORM\Column(name="MatchingOrder", type="integer", nullable=false)
     */
    protected $matchingOrder;

    /**
     * @var bool
     * @ORM\Column(name="IsBusiness", type="boolean", nullable=false)
     */
    protected $isBusiness;

    /**
     * @var bool
     * @ORM\Column(name="IsDiscontinued", type="boolean", nullable=false)
     */
    protected $isDiscontinued;

    /**
     * @var bool
     * @ORM\Column(name="IsCashBackOnly", type="boolean", nullable=false)
     */
    protected $isCashBackOnly;

    /**
     * @ORM\Column(name="CashBackType", type="integer", nullable=true)
     */
    protected ?int $cashBackType;

    /**
     * @var string
     * @ORM\Column(name="Description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     * @ORM\Column(name="PointName", type="text", nullable=true)
     */
    protected $pointName;

    /**
     * @var bool
     * @ORM\Column(name="VisibleInList", type="boolean", nullable=true)
     */
    protected $visibleInList;

    /**
     * @var bool
     * @ORM\Column(name="VisibleOnLanding", type="boolean", nullable=true)
     */
    protected $visibleOnLanding;

    /**
     * @var int
     * @ORM\Column(name="ExcludeCardsId", type="simple_array", nullable=true)
     */
    protected $excludeCardsId;

    /**
     * @var int
     * @ORM\Column(name="SortIndex", type="integer", nullable=true)
     */
    protected $sortIndex;

    /**
     * @ORM\Column(name="RankIndex", type="integer", nullable=true)
     */
    protected ?int $rankIndex;

    /**
     * @var string
     * @ORM\Column(name="Text", type="text", nullable=true)
     */
    protected $text;

    /**
     * @var int
     * @ORM\Column(name="PictureVer", type="integer", nullable=true)
     */
    protected $pictureVer;

    /**
     * @var string
     * @ORM\Column(name="PictureExt", type="string", nullable=true)
     */
    protected $pictureExt;

    /**
     * Unmapped property to handle file uploads.
     */
    protected $pictureFile;

    /**
     * @var CreditCardShoppingCategoryGroup[]|Collection
     * @ORM\OneToMany(
     *     targetEntity="CreditCardShoppingCategoryGroup",
     *     mappedBy="creditCard",
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true,
     *     indexBy="kind"
     * )
     * @ORM\OrderBy({"sortIndex" = "ASC"})
     */
    protected $multipliers;

    /**
     * @var QsCreditCard
     * @ORM\OneToOne(targetEntity="QsCreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="QsCreditCardID", referencedColumnName="QsCreditCardID")
     * })
     */
    protected $qsCreditCard;

    /**
     * @ORM\Column(name="ForeignTransactionFee", type="float", nullable=true)
     */
    protected ?float $foreignTransactionFee;

    /**
     * @ORM\Column(name="IsApiReady", type="boolean", nullable=false)
     */
    protected bool $isApiReady;

    /**
     * @ORM\Column(name="IsOfferPriorityPass", type="boolean", nullable=false)
     */
    protected bool $isOfferPriorityPass;

    /**
     * @ORM\Column(name="IsVisibleInAll", type="boolean", nullable=false)
     */
    protected bool $isVisibleInAll;

    /**
     * @ORM\Column(name="IsVisibleInBest", type="boolean", nullable=false)
     */
    protected bool $isVisibleInBest;

    /**
     * @ORM\Column(name="IsNonAffiliateDisclosure", type="boolean", nullable=false)
     */
    protected bool $isNonAffiliateDisclosure;

    public function __construct(?int $id = null)
    {
        $this->multipliers = new ArrayCollection();
        $this->id = $id;
    }

    public function __toString()
    {
        return empty($this->name) ? "" : $this->name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return $this
     */
    public function setProvider(Provider $provider)
    {
        $this->provider = $provider;

        return $this;
    }

    public function getCobrandProvider(): ?Provider
    {
        return $this->cobrandProvider;
    }

    public function setCobrandProvider(Provider $cobrandProvider): self
    {
        $this->cobrandProvider = $cobrandProvider;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPatterns()
    {
        return $this->patterns;
    }

    /**
     * @return $this
     */
    public function setPatterns(string $patterns)
    {
        $this->patterns = $patterns;

        return $this;
    }

    public function getHistoryPatterns(): ?string
    {
        return $this->historyPatterns;
    }

    public function setHistoryPatterns(string $historyPatterns): self
    {
        $this->historyPatterns = $historyPatterns;

        return $this;
    }

    public function getCobrandSubAccPatterns(): ?string
    {
        return $this->cobrandSubAccPatterns;
    }

    public function setCobrandSubAccPatterns(?string $cobrandSubAccPatterns): self
    {
        $this->cobrandSubAccPatterns = $cobrandSubAccPatterns;

        return $this;
    }

    /**
     * @return string
     */
    public function getClickURL()
    {
        return $this->clickURL;
    }

    /**
     * @param string $clickURL
     * @return $this
     */
    public function setClickURL($clickURL)
    {
        $this->clickURL = $clickURL;

        return $this;
    }

    /**
     * @return int
     */
    public function getMatchingOrder()
    {
        return $this->matchingOrder;
    }

    public function setMatchingOrder(int $matchingOrder)
    {
        $this->matchingOrder = $matchingOrder;
    }

    public function isBusiness(): ?bool
    {
        return $this->isBusiness;
    }

    /**
     * @return $this
     */
    public function setIsBusiness(bool $isBusiness)
    {
        $this->isBusiness = $isBusiness;

        return $this;
    }

    public function isDiscontinued(): ?bool
    {
        return $this->isDiscontinued;
    }

    /**
     * @return $this
     */
    public function setIsDiscontinued(bool $isDiscontinued)
    {
        $this->isDiscontinued = $isDiscontinued;

        return $this;
    }

    public function isCashBackOnly(): ?bool
    {
        return $this->isCashBackOnly;
    }

    public function setIsCashBackOnly(bool $isCashBackOnly): void
    {
        $this->isCashBackOnly = $isCashBackOnly;
    }

    public function getCashBackType(): ?int
    {
        return $this->cashBackType;
    }

    public function setCashBackType(?int $cashBackType): self
    {
        $this->cashBackType = $cashBackType;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getPointName(): ?string
    {
        return $this->pointName;
    }

    public function setPointName(string $pointName): self
    {
        $this->pointName = $pointName;

        return $this;
    }

    public function getDisplayNameFormat(): ?string
    {
        return $this->displayNameFormat;
    }

    public function setDisplayNameFormat(string $displayNameFormat): self
    {
        $this->displayNameFormat = $displayNameFormat;

        return $this;
    }

    /**
     * @return CreditCardShoppingCategoryGroup[]|PersistentCollection
     */
    public function getMultipliers()
    {
        return $this->multipliers;
    }

    /* for admin list */
    public function getMultipliersToString()
    {
        $result = [];

        foreach ($this->multipliers as $item) {
            $result[] = $item->getMultiplier() . 'x - ' . $item->getShoppingCategoryGroup();
        }

        return implode("<br />", $result);
    }

    /**
     * @param CreditCardShoppingCategoryGroup[]|PersistentCollection $multipliers
     * @return $this
     */
    public function setMultipliers($multipliers)
    {
        $this->multipliers = $multipliers;

        return $this;
    }

    public function addMultiplier(CreditCardShoppingCategoryGroup $multiplier)
    {
        $multiplier->setCreditCard($this);
        $this->multipliers->add($multiplier);
    }

    public function removeMultiplier(CreditCardShoppingCategoryGroup $multiplier)
    {
        $this->multipliers->removeElement($multiplier);
    }

    public static function formatCreditCardName(
        string $subAccountDisplayName,
        string $format,
        int $providerId
    ): string {
        $isPattern = strpos($format, self::NUMBER_ENDING_PARAM_FORMAT) !== false;

        if (
            preg_match(
                self::NUMBER_ENDING[$providerId] ?? self::NUMBER_ENDING_DEFAULT,
                $subAccountDisplayName,
                $cardEnding
            )
        ) {
            if ($isPattern) {
                return trim(str_replace(self::NUMBER_ENDING_PARAM_FORMAT, sprintf('(...%s)', $cardEnding[0]), $format));
            }

            return sprintf('%s (...%s)', $format, $cardEnding[0]);
        }

        if ($isPattern) {
            return trim(str_replace(self::NUMBER_ENDING_PARAM_FORMAT, '', $format));
        }

        return $format;
    }

    /**
     * @return $this
     */
    public function setQsCreditCard(?QsCreditCard $qsCreditCard): self
    {
        $this->qsCreditCard = $qsCreditCard;

        return $this;
    }

    public function getQsCreditCard(): ?QsCreditCard
    {
        return $this->qsCreditCard;
    }

    public function getForeignTransactionFee(): ?float
    {
        return $this->foreignTransactionFee;
    }

    public function setForeignTransactionFee(?float $fee): self
    {
        $this->foreignTransactionFee = $fee;

        return $this;
    }

    /**
     * @return $this
     */
    public function setCardFullName(?string $fullName): self
    {
        $this->cardFullName = $fullName;

        return $this;
    }

    public function getCardFullName(): ?string
    {
        return $this->cardFullName;
    }

    public function isVisibleInList(): ?bool
    {
        return $this->visibleInList;
    }

    /**
     * @return $this
     */
    public function setVisibleInList(?string $visibleInList): self
    {
        $this->visibleInList = $visibleInList;

        return $this;
    }

    public function setExcludeCardsId(?array $cardIds): self
    {
        if (is_array($cardIds)) {
            $cardIds = array_map('intval', $cardIds);
            $cardIds = array_unique($cardIds);
        }
        $this->excludeCardsId = empty($cardIds) ? null : $cardIds;

        return $this;
    }

    public function getExcludeCardsId(): ?array
    {
        return is_array($this->excludeCardsId) && !empty($this->excludeCardsId) ? array_map('intval', $this->excludeCardsId) : null;
    }

    public function isVisibleOnLanding(): ?bool
    {
        return $this->visibleOnLanding;
    }

    public function setVisibleOnLanding(?bool $visibleOnLanding): self
    {
        $this->visibleOnLanding = $visibleOnLanding;

        return $this;
    }

    /**
     * @return $this
     */
    public function setSortIndex(?int $sortIndex): self
    {
        $this->sortIndex = $sortIndex;

        return $this;
    }

    public function getSortIndex(): ?int
    {
        return $this->sortIndex;
    }

    public function setRankIndex(?int $rankIndex): self
    {
        $this->rankIndex = $rankIndex;

        return $this;
    }

    public function getRankIndex(): ?int
    {
        return $this->rankIndex;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setDirectClickURL(?string $directClickUrl): self
    {
        $this->directClickURL = $directClickUrl;

        return $this;
    }

    public function getDirectClickURL(): ?string
    {
        return $this->directClickURL;
    }

    public function setPictureVer(?int $version): self
    {
        $this->pictureVer = $version;

        return $this;
    }

    public function getPictureVer(): ?int
    {
        return $this->pictureVer;
    }

    public function setPictureExt(?string $extension): self
    {
        $this->pictureExt = $extension;

        return $this;
    }

    public function getPictureExt(): ?string
    {
        return $this->pictureExt;
    }

    public function getPicturePath(string $size = 'original'): ?string
    {
        if (empty($this->pictureExt) || empty($this->pictureVer)) {
            return null;
        }

        if (!\array_key_exists($size, self::PICTURE_SIZES)) {
            throw new \InvalidArgumentException('Unsupported size');
        }

        return Image::getPath($this->id, self::PICTURE_FOLDERNAME, $size, $this->pictureVer, $this->pictureExt);
    }

    public function setPictureFile(?UploadedFile $file = null): self
    {
        $this->pictureFile = $file;

        return $this;
    }

    public function getPictureFile(): ?UploadedFile
    {
        return $this->pictureFile;
    }

    public function uploadPictureFile()
    {
        if (null === $this->pictureFile) {
            return null;
        }

        $data = file_get_contents($this->getPictureFile()->getPathname());

        if (empty($data)) {
            return null;
        }

        try {
            $image = new Image($data, self::PICTURE_FOLDERNAME, self::PICTURE_SIZES);
        } catch (\Exception $e) {
            throw new \Exception('Error "' . $e->getMessage() . '"');
        }

        $image
            ->createImage($this->id)
            ->resizeImage($this->id, 'medium', self::PICTURE_SIZES['medium']);
        $this->setPictureExt($image->getExtension());
        $this->setPictureVer($image->getVersion());
    }

    public function getBaseAmountForPoints(float $amount): float
    {
        if ($this->provider->getId() === Provider::AMEX_ID) {
            return round($amount);
        }

        if ($this->provider->getId() === Provider::CAPITAL_ONE_ID) {
            // 7.29 -> 7.5
            // 7.19 -> 7.0
            return round(round($amount * 2) / 2, 1);
        }

        return $amount;
    }

    public function isApiReady(): bool
    {
        return $this->isApiReady;
    }

    public function setIsApiReady(bool $isApiReady): self
    {
        $this->isApiReady = $isApiReady;

        return $this;
    }

    public function isOfferPriorityPass(): bool
    {
        return $this->isOfferPriorityPass;
    }

    public function setIsOfferPriorityPass(bool $isOfferPriorityPass): self
    {
        $this->isOfferPriorityPass = $isOfferPriorityPass;

        return $this;
    }

    public function isVisibleInAll(): bool
    {
        return $this->isVisibleInAll;
    }

    public function setIsVisibleInAll(bool $isVisibleInAll): self
    {
        $this->isVisibleInAll = $isVisibleInAll;

        return $this;
    }

    public function isVisibleInBest(): bool
    {
        return $this->isVisibleInBest;
    }

    public function setIsVisibleInBest(bool $isVisibleInBest): self
    {
        $this->isVisibleInBest = $isVisibleInBest;

        return $this;
    }

    public function isNonAffiliateDisclosure(): bool
    {
        return $this->isNonAffiliateDisclosure;
    }

    public function setIsNonAffiliateDisclosure(bool $isNonAffiliateDisclosure): self
    {
        $this->isNonAffiliateDisclosure = $isNonAffiliateDisclosure;

        return $this;
    }
}
