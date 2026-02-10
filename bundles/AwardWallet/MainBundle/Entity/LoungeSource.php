<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\AbstractOpeningHours;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lounge.
 *
 * @ORM\Table(name="LoungeSource")
 * @ORM\Entity
 */
class LoungeSource implements LoungeInterface
{
    public const ASSET_TYPE_IMG = 'image';
    public const ASSET_TYPE_URL = 'url';

    /**
     * @var int
     * @ORM\Column(name="LoungeSourceID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="AirportCode", type="string", length=3, nullable=false)
     */
    private $airportCode;

    /**
     * @var string|null
     * @ORM\Column(name="Terminal", type="string", length=100, nullable=true)
     */
    private $terminal;

    /**
     * @var string|null
     * @ORM\Column(name="Gate", type="string", length=100, nullable=true)
     */
    private $gate;

    /**
     * @var string|null
     * @ORM\Column(name="Gate2", type="string", length=100, nullable=true)
     */
    private $gate2;

    /**
     * @var AbstractOpeningHours|null
     * @ORM\Column(name="OpeningHours", type="jms_json", nullable=true)
     */
    private $openingHours;

    /**
     * @var bool
     * @ORM\Column(name="IsAvailable", type="boolean", nullable=true)
     */
    private $isAvailable;

    /**
     * @var string|null
     * @ORM\Column(name="Location", type="string", length=4096, nullable=true)
     */
    private $location;

    /**
     * @var string|null
     * @ORM\Column(name="AdditionalInfo", type="text", length=65535, nullable=true)
     */
    private $additionalInfo;

    /**
     * @var string|null
     * @ORM\Column(name="Amenities", type="string", length=4096, nullable=true)
     */
    private $amenities;

    /**
     * @var string|null
     * @ORM\Column(name="Rules", type="text", length=65535, nullable=true)
     */
    private $rules;

    /**
     * @var bool|null
     * @ORM\Column(name="IsRestaurant", type="boolean", nullable=true)
     */
    private $isRestaurant;

    /**
     * @var string
     * @ORM\Column(name="SourceCode", type="string", length=50, nullable=false)
     */
    private $sourceCode;

    /**
     * @var string
     * @ORM\Column(name="SourceID", type="string", length=255, nullable=false)
     */
    private $sourceId;

    /**
     * @ORM\Column(name="Assets", type="json", nullable=false)
     */
    private $assets = [];

    /**
     * @var string
     * @ORM\Column(name="PageBody", type="text", nullable=false)
     */
    private $pageBody;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updateDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="DeleteDate", type="datetime", nullable=true)
     */
    private $deleteDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="ParseDate", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $parseDate;

    /**
     * @var bool
     * @ORM\Column(name="PriorityPassAccess", type="boolean", nullable=true)
     */
    private $priorityPassAccess;

    /**
     * @var bool
     * @ORM\Column(name="AmexPlatinumAccess", type="boolean", nullable=true)
     */
    private $amexPlatinumAccess;

    /**
     * @var bool
     * @ORM\Column(name="DragonPassAccess", type="boolean", nullable=true)
     */
    private $dragonPassAccess;

    /**
     * @var bool
     * @ORM\Column(name="LoungeKeyAccess", type="boolean", nullable=true)
     */
    private $loungeKeyAccess;

    /**
     * @var Lounge
     * @ORM\ManyToOne(targetEntity="Lounge")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="LoungeID", referencedColumnName="LoungeID")
     * })
     */
    private $lounge;

    /**
     * @var Airline[]|Collection
     * @ORM\ManyToMany(targetEntity="AwardWallet\MainBundle\Entity\Airline")
     * @ORM\JoinTable(
     *     name="LoungeSourceAirline",
     *     joinColumns={@ORM\JoinColumn(name="LoungeSourceID", referencedColumnName="LoungeSourceID")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="AirlineID", referencedColumnName="AirlineID")}
     * )
     */
    private $airlines;

    /**
     * @var Alliance[]|Collection
     * @ORM\ManyToMany(targetEntity="AwardWallet\MainBundle\Entity\Alliance")
     * @ORM\JoinTable(
     *     name="LoungeSourceAlliance",
     *     joinColumns={@ORM\JoinColumn(name="LoungeSourceID", referencedColumnName="LoungeSourceID")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="AllianceID", referencedColumnName="AllianceID")}
     * )
     */
    private $alliances;

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->updateDate = new \DateTime();
        $this->airlines = new ArrayCollection();
        $this->alliances = new ArrayCollection();
    }

    public function __toString()
    {
        return sprintf(
            '%sairport: %s%s, source: %s,%s name: %s',
            $this->getId() ? sprintf('#%d, ', $this->getId()) : '',
            $this->getAirportCode(),
            $this->getTerminal() ? sprintf(' (%s)', $this->getTerminal()) : '',
            $this->getSourceCode(),
            $this->getSourceId() ? sprintf(' id: %s,', $this->getSourceId()) : '',
            $this->getName()
        );
    }

    public function __clone()
    {
        $this->id = null;
        $this->lounge = null;
        $this->deleteDate = null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAirportCode(): ?string
    {
        return $this->airportCode;
    }

    public function setAirportCode(?string $airportCode): self
    {
        $this->airportCode = $airportCode;

        return $this;
    }

    public function getTerminal(): ?string
    {
        return $this->terminal;
    }

    public function setTerminal(?string $terminal): self
    {
        $this->terminal = $terminal;

        return $this;
    }

    public function getGate(): ?string
    {
        return $this->gate;
    }

    public function setGate(?string $gate): self
    {
        $this->gate = $gate;

        return $this;
    }

    public function getGate2(): ?string
    {
        return $this->gate2;
    }

    public function setGate2(?string $gate): self
    {
        $this->gate2 = $gate;

        return $this;
    }

    public function getOpeningHours(): ?AbstractOpeningHours
    {
        return $this->openingHours;
    }

    public function setOpeningHours(?AbstractOpeningHours $openingHours): self
    {
        $this->openingHours = $openingHours;

        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setAvailable(?bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    public function setIsAvailable(?bool $isAvailable): self
    {
        return $this->setAvailable($isAvailable);
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getAdditionalInfo(): ?string
    {
        return $this->additionalInfo;
    }

    public function setAdditionalInfo(?string $additionalInfo): self
    {
        $this->additionalInfo = $additionalInfo;

        return $this;
    }

    public function getAmenities(): ?string
    {
        return $this->amenities;
    }

    public function setAmenities(?string $amenities): self
    {
        $this->amenities = $amenities;

        return $this;
    }

    public function getRules(): ?string
    {
        return $this->rules;
    }

    public function setRules(?string $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function isRestaurant(): ?bool
    {
        return $this->isRestaurant;
    }

    public function setIsRestaurant(?bool $isRestaurant): self
    {
        $this->isRestaurant = $isRestaurant;

        return $this;
    }

    public function getSourceCode(): ?string
    {
        return $this->sourceCode;
    }

    public function setSourceCode(string $sourceCode): self
    {
        $this->sourceCode = $sourceCode;

        return $this;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    public function setSourceId(string $sourceId): self
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function setAssets(array $assets): self
    {
        $this->assets = $assets;

        return $this;
    }

    /**
     * @param string[] $urls
     */
    public function setImages(array $urls): self
    {
        foreach ($this->assets as $i => $asset) {
            if (!isset($asset['type']) || $asset['type'] === self::ASSET_TYPE_IMG) {
                unset($this->assets[$i]);
            }
        }

        $this->assets = array_values($this->assets);

        foreach ($urls as $url) {
            $this->assets[] = [
                'url' => $url,
                'type' => self::ASSET_TYPE_IMG,
            ];
        }

        return $this;
    }

    public function setUrl(string $url): self
    {
        foreach ($this->assets as $i => $asset) {
            if (!isset($asset['type']) || $asset['type'] === self::ASSET_TYPE_URL) {
                unset($this->assets[$i]);
            }
        }

        $this->assets = array_values($this->assets);

        $this->assets[] = [
            'url' => $url,
            'type' => self::ASSET_TYPE_URL,
        ];

        return $this;
    }

    public function getUrl(): ?string
    {
        foreach ($this->assets as $asset) {
            if (isset($asset['type']) && $asset['type'] === self::ASSET_TYPE_URL) {
                return $asset['url'] ?? null;
            }
        }

        return null;
    }

    public function getPageBody(): ?string
    {
        return $this->pageBody;
    }

    public function setPageBody(string $pageBody): self
    {
        $this->pageBody = $pageBody;

        return $this;
    }

    public function getCreateDate(): ?\DateTime
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->updateDate;
    }

    public function setUpdateDate(\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getDeleteDate(): ?\DateTime
    {
        return $this->deleteDate;
    }

    public function setDeleteDate(?\DateTime $deleteDate): self
    {
        $this->deleteDate = $deleteDate;

        return $this;
    }

    public function getParseDate(): ?\DateTime
    {
        return $this->parseDate;
    }

    public function setParseDate(\DateTime $parseDate): self
    {
        $this->parseDate = $parseDate;

        return $this;
    }

    public function isPriorityPassAccess(): ?bool
    {
        return $this->priorityPassAccess;
    }

    public function setPriorityPassAccess(?bool $priorityPassAccess): self
    {
        $this->priorityPassAccess = $priorityPassAccess;

        return $this;
    }

    public function isAmexPlatinumAccess(): ?bool
    {
        return $this->amexPlatinumAccess;
    }

    public function setAmexPlatinumAccess(?bool $amexPlatinumAccess): self
    {
        $this->amexPlatinumAccess = $amexPlatinumAccess;

        return $this;
    }

    public function isDragonPassAccess(): ?bool
    {
        return $this->dragonPassAccess;
    }

    public function setDragonPassAccess(?bool $dragonPassAccess): self
    {
        $this->dragonPassAccess = $dragonPassAccess;

        return $this;
    }

    public function isLoungeKeyAccess(): ?bool
    {
        return $this->loungeKeyAccess;
    }

    public function setLoungeKeyAccess(?bool $loungeKeyAccess): self
    {
        $this->loungeKeyAccess = $loungeKeyAccess;

        return $this;
    }

    public function getLounge(): ?Lounge
    {
        return $this->lounge;
    }

    public function setLounge(?Lounge $lounge): self
    {
        $this->lounge = $lounge;

        return $this;
    }

    /**
     * @return Airline[]|Collection
     */
    public function getAirlines()
    {
        return $this->airlines;
    }

    /**
     * @param Airline[]|Collection|null $airlines
     */
    public function setAirlines($airlines): self
    {
        if (is_null($airlines)) {
            $this->airlines = new ArrayCollection();
        } else {
            if ($airlines instanceof Collection) {
                $airlines = $airlines->toArray();
            }

            foreach ($airlines as $airline) {
                $this->addAirline($airline);
            }

            foreach ($this->airlines as $airline) {
                if (!in_array($airline, $airlines)) {
                    $this->removeAirline($airline);
                }
            }
        }

        return $this;
    }

    public function addAirline(Airline $airline): self
    {
        if (!$this->airlines->contains($airline)) {
            $this->airlines->add($airline);
        }

        return $this;
    }

    public function removeAirline(Airline $airline): self
    {
        if ($this->airlines->contains($airline)) {
            $this->airlines->removeElement($airline);
        }

        return $this;
    }

    /**
     * @return Alliance[]|Collection
     */
    public function getAlliances()
    {
        return $this->alliances;
    }

    /**
     * @param Alliance[]|Collection|null $alliances
     */
    public function setAlliances($alliances): self
    {
        if (is_null($alliances)) {
            $this->alliances = new ArrayCollection();
        } else {
            if ($alliances instanceof Collection) {
                $alliances = $alliances->toArray();
            }

            foreach ($alliances as $alliance) {
                $this->addAlliance($alliance);
            }

            foreach ($this->alliances as $alliance) {
                if (!in_array($alliance, $alliances)) {
                    $this->removeAlliance($alliance);
                }
            }
        }

        return $this;
    }

    public function addAlliance(Alliance $alliance): self
    {
        if (!$this->alliances->contains($alliance)) {
            $this->alliances->add($alliance);
        }

        return $this;
    }

    public function removeAlliance(Alliance $alliance): self
    {
        if ($this->alliances->contains($alliance)) {
            $this->alliances->removeElement($alliance);
        }

        return $this;
    }

    public function createLounge(): Lounge
    {
        return (new Lounge())
            ->setName($this->getName())
            ->setAirportCode($this->getAirportCode())
            ->setTerminal($this->getTerminal())
            ->setGate($this->getGate())
            ->setGate2($this->getGate2())
            ->setOpeningHours($this->getOpeningHours())
            ->setAvailable($this->isAvailable())
            ->setLocation($this->getLocation())
            ->setAdditionalInfo($this->getAdditionalInfo())
            ->setAmenities($this->getAmenities())
            ->setRules($this->getRules())
            ->setIsRestaurant($this->isRestaurant())
            ->setPriorityPassAccess($this->isPriorityPassAccess())
            ->setAmexPlatinumAccess($this->isAmexPlatinumAccess())
            ->setDragonPassAccess($this->isDragonPassAccess())
            ->setLoungeKeyAccess($this->isLoungeKeyAccess())
            ->setVisible(true)
            ->setAirlines($this->getAirlines())
            ->setAlliances($this->getAlliances());
    }
}
