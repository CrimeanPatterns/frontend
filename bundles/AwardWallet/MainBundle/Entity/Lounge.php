<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Service\Lounge\LoungeInterface;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\AbstractOpeningHours;
use AwardWallet\MainBundle\Service\Lounge\OpeningHours\StructuredOpeningHours;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Lounge.
 *
 * @ORM\Table(name="Lounge")
 * @ORM\Entity
 */
class Lounge implements LoungeInterface
{
    /**
     * @var int
     * @ORM\Column(name="LoungeID", type="integer", nullable=false)
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
     * @var StructuredOpeningHours|null
     * @ORM\Column(name="OpeningHoursAi", type="jms_json", nullable=true)
     */
    private $openingHoursAi;

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
     * @ORM\Column(name="LocationParaphrased", type="string", length=4096, nullable=true)
     */
    private $locationParaphrased;

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
     * @var Usr|null
     * @ORM\OneToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="CheckedBy", referencedColumnName="UserID", nullable=true)
     * })
     */
    private $checkedBy;

    /**
     * @var \DateTime|null
     * @ORM\Column(name="CheckedDate", type="datetime", nullable=true)
     */
    private $checkedDate;

    /**
     * @var bool
     * @ORM\Column(name="Visible", type="boolean", nullable=false)
     */
    private $visible = false;

    /**
     * @var bool
     * @ORM\Column(name="AttentionRequired", type="boolean", nullable=false)
     */
    private $attentionRequired = false;

    /**
     * @ORM\Column(name="State", type="json", nullable=true)
     */
    private $state;

    /**
     * @var Airline[]|Collection
     * @ORM\ManyToMany(targetEntity="AwardWallet\MainBundle\Entity\Airline")
     * @ORM\JoinTable(
     *     name="LoungeAirline",
     *     joinColumns={@ORM\JoinColumn(name="LoungeID", referencedColumnName="LoungeID")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="AirlineID", referencedColumnName="AirlineID")}
     * )
     */
    private $airlines;

    /**
     * @var Alliance[]|Collection
     * @ORM\ManyToMany(targetEntity="AwardWallet\MainBundle\Entity\Alliance")
     * @ORM\JoinTable(
     *     name="LoungeAlliance",
     *     joinColumns={@ORM\JoinColumn(name="LoungeID", referencedColumnName="LoungeID")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="AllianceID", referencedColumnName="AllianceID")}
     * )
     */
    private $alliances;

    /**
     * @var LoungeSource[]
     * @ORM\OneToMany(targetEntity="LoungeSource", mappedBy="lounge", cascade={"persist", "remove"})
     */
    private $sources;

    /**
     * @var LoungeAction[]
     * @ORM\OneToMany(targetEntity="LoungeAction", mappedBy="lounge", cascade={"persist", "remove"})
     */
    private $actions;

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->updateDate = new \DateTime();
        $this->airlines = new ArrayCollection();
        $this->alliances = new ArrayCollection();
        $this->sources = new ArrayCollection();
        $this->actions = new ArrayCollection();
    }

    public function __toString()
    {
        return sprintf(
            '%sairport: %s%s, name: %s',
            $this->getId() ? sprintf('#%d, ', $this->getId()) : '',
            $this->getAirportCode(),
            $this->getTerminal() ? sprintf(' (%s)', $this->getTerminal()) : '',
            $this->getName()
        );
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
        if (
            is_null($this->openingHours)
            || is_null($openingHours)
            || !$this->openingHours->isEquals($openingHours)
        ) {
            $this->openingHoursAi = null;
        }

        $this->openingHours = $openingHours;

        return $this;
    }

    public function getOpeningHoursAi(): ?StructuredOpeningHours
    {
        return $this->openingHoursAi;
    }

    public function setOpeningHoursAi(?StructuredOpeningHours $openingHoursAi): self
    {
        $this->openingHoursAi = $openingHoursAi;

        return $this;
    }

    public function getOpeningHoursFinal(): ?AbstractOpeningHours
    {
        return $this->getOpeningHoursAi() ?? $this->getOpeningHours();
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
        if ($this->location !== $location) {
            $this->locationParaphrased = null;
        }

        $this->location = $location;

        return $this;
    }

    public function getLocationParaphrased(): ?string
    {
        return $this->locationParaphrased;
    }

    public function setLocationParaphrased(?string $locationParaphrased): self
    {
        $this->locationParaphrased = $locationParaphrased;

        return $this;
    }

    public function getFinalLocation(): ?string
    {
        return $this->getLocationParaphrased() ?? $this->getLocation();
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

    public function getCheckedBy(): ?Usr
    {
        return $this->checkedBy;
    }

    public function setCheckedBy(?Usr $checkedBy): self
    {
        $this->checkedBy = $checkedBy;

        return $this;
    }

    public function getCheckedDate(): ?\DateTime
    {
        return $this->checkedDate;
    }

    public function setCheckedDate(?\DateTime $checkedDate): self
    {
        $this->checkedDate = $checkedDate;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    public function isAttentionRequired(): bool
    {
        return $this->attentionRequired;
    }

    public function setAttentionRequired(bool $attentionRequired): self
    {
        $this->attentionRequired = $attentionRequired;

        return $this;
    }

    public function getState(): ?array
    {
        return $this->state;
    }

    public function setState(?array $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function addStateMessages(array $messages): self
    {
        $state = $this->getState();

        if (!is_array($state)) {
            $state = [];
        }

        $state = array_merge($state, $messages);
        $this->setState(empty($state) ? null : $state);
        $this->setAttentionRequired(!empty($state));

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

    /**
     * @return LoungeSource[]|Collection
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * @param LoungeSource[]|Collection $sources
     */
    public function setSources(array $sources): self
    {
        $this->sources = new ArrayCollection($sources);

        return $this;
    }

    /**
     * @return LoungeAction[]|Collection
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param LoungeAction[]|Collection $actions
     */
    public function setActions($actions): self
    {
        $this->actions = $actions;

        return $this;
    }
}
