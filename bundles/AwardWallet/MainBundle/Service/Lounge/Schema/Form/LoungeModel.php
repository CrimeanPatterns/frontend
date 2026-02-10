<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema\Form;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Alliance;
use AwardWallet\MainBundle\Entity\LoungeSource;
use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

class LoungeModel extends AbstractEntityAwareModel
{
    private ?int $id = null;

    /**
     * @Assert\NotBlank
     * @Assert\Type(type="string")
     * @Assert\Length(max = "250")
     */
    private ?string $name = null;

    /**
     * @Assert\NotBlank
     * @Assert\Type(type="string")
     * @Assert\Length(min = "3", max = "3", allowEmptyString="true")
     */
    private ?string $airportCode = null;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(max = "100")
     */
    private ?string $terminal = null;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(max = "100")
     */
    private ?string $gate = null;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(max = "100")
     */
    private ?string $gate2 = null;

    private ?string $openingHours = null;

    /**
     * @Assert\Type(type="bool")
     */
    private ?bool $isRawOpeningHours = null;

    /**
     * @Assert\Type(type="bool")
     */
    private ?bool $isAvailable = null;

    /**
     * @Assert\Type(type="bool")
     */
    private ?bool $priorityPassAccess = null;

    /**
     * @Assert\Type(type="bool")
     */
    private ?bool $amexPlatinumAccess = null;

    /**
     * @Assert\Type(type="bool")
     */
    private ?bool $dragonPassAccess = null;

    /**
     * @Assert\Type(type="bool")
     */
    private ?bool $loungeKeyAccess = null;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(max = "4000")
     */
    private ?string $location = null;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(max = "4000")
     */
    private ?string $locationParaphrased = null;

    /**
     * @Assert\Type(type="string")
     */
    private ?string $additionalInfo = null;

    /**
     * @Assert\Type(type="string")
     * @Assert\Length(max = "4000")
     */
    private ?string $amenities = null;

    /**
     * @Assert\Type(type="string")
     */
    private ?string $rules = null;

    /**
     * @Assert\Type(type="bool")
     */
    private ?bool $isRestaurant = null;

    private array $sources = [];

    private ?\DateTime $createDate = null;

    private ?\DateTime $updateDate = null;

    private ?string $checkedBy = null;

    private ?\DateTime $checkedDate = null;

    /**
     * @Assert\Type(type="bool")
     */
    private bool $visible = false;

    /**
     * @var Airline[]|Collection
     */
    private $airlines;

    /**
     * @var Alliance[]|Collection
     */
    private $alliances;

    private ?FreezeActionModel $freezeAction = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
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

    public function setGate2(?string $gate2): self
    {
        $this->gate2 = $gate2;

        return $this;
    }

    public function getOpeningHours(): ?string
    {
        return $this->openingHours;
    }

    public function setOpeningHours(?string $openingHours): self
    {
        $this->openingHours = $openingHours;

        return $this;
    }

    public function getIsRawOpeningHours(): ?bool
    {
        return $this->isRawOpeningHours;
    }

    public function setIsRawOpeningHours(?bool $isRawOpeningHours): self
    {
        $this->isRawOpeningHours = $isRawOpeningHours;

        return $this;
    }

    public function getIsAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(?bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    public function getPriorityPassAccess(): ?bool
    {
        return $this->priorityPassAccess;
    }

    public function setPriorityPassAccess(?bool $priorityPassAccess): self
    {
        $this->priorityPassAccess = $priorityPassAccess;

        return $this;
    }

    public function getAmexPlatinumAccess(): ?bool
    {
        return $this->amexPlatinumAccess;
    }

    public function setAmexPlatinumAccess(?bool $amexPlatinumAccess): self
    {
        $this->amexPlatinumAccess = $amexPlatinumAccess;

        return $this;
    }

    public function getDragonPassAccess(): ?bool
    {
        return $this->dragonPassAccess;
    }

    public function setDragonPassAccess(?bool $dragonPassAccess): self
    {
        $this->dragonPassAccess = $dragonPassAccess;

        return $this;
    }

    public function getLoungeKeyAccess(): ?bool
    {
        return $this->loungeKeyAccess;
    }

    public function setLoungeKeyAccess(?bool $loungeKeyAccess): self
    {
        $this->loungeKeyAccess = $loungeKeyAccess;

        return $this;
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

    public function getLocationParaphrased(): ?string
    {
        return $this->locationParaphrased;
    }

    public function setLocationParaphrased(?string $locationParaphrased): self
    {
        $this->locationParaphrased = $locationParaphrased;

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

    public function getIsRestaurant(): ?bool
    {
        return $this->isRestaurant;
    }

    public function setIsRestaurant(?bool $isRestaurant): self
    {
        $this->isRestaurant = $isRestaurant;

        return $this;
    }

    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * @param LoungeSource[]|Collection $sources
     */
    public function setSources($sources): self
    {
        if ($sources instanceof Collection) {
            $sources = $sources->toArray();
        }

        $this->sources = $sources;

        return $this;
    }

    public function getCreateDate(): ?\DateTime
    {
        return $this->createDate;
    }

    public function setCreateDate(?\DateTime $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?\DateTime $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getCheckedBy(): ?string
    {
        return $this->checkedBy;
    }

    public function setCheckedBy(?string $checkedBy): self
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

    /**
     * @return Airline[]|Collection
     */
    public function getAirlines()
    {
        return $this->airlines;
    }

    /**
     * @param Airline[]|Collection $airlines
     */
    public function setAirlines($airlines): self
    {
        $this->airlines = $airlines;

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
     * @param Alliance[]|Collection $alliances
     */
    public function setAlliances($alliances): self
    {
        $this->alliances = $alliances;

        return $this;
    }

    public function getFreezeAction(): ?FreezeActionModel
    {
        return $this->freezeAction;
    }

    public function setFreezeAction(?FreezeActionModel $freezeAction): self
    {
        $this->freezeAction = $freezeAction;

        return $this;
    }
}
