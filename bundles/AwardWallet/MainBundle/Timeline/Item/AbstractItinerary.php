<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\Common\Entity\Geotag;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Timeline\Diff\Changes;
use AwardWallet\MainBundle\Timeline\PhonesSection;

abstract class AbstractItinerary extends AbstractItem implements ItineraryInterface
{
    /**
     * trip has changed, show warning icon over main icon.
     */
    protected bool $changed;

    /**
     * info for showing trip map in the middle of title, like JFK > LAX, 12:00pm.
     */
    protected ?Map $map = null;

    /**
     * confirmation number.
     */
    protected ?string $confNo = null;

    /**
     * where this segment came from, Entity instance, like Rental or Tripsegment.
     *
     * @var Itinerary|Tripsegment
     */
    protected $source;

    /**
     * from which account this segment was grabbed.
     */
    protected ?Account $account = null;

    /**
     * from which account this segment was grabbed.
     */
    protected ?Provider $provider = null;

    /**
     * @var PhonesSection[]
     */
    protected array $phones;

    protected ?Changes $changes = null;

    protected ?Geotag $geotag = null;

    /**
     * Useragent in context of current user.
     */
    protected ?Useragent $agent = null;

    protected ?array $files = null;

    public function __construct(
        int $id,
        \DateTime $startDate,
        ?\DateTime $endDate = null,
        ?\DateTime $localDate = null,
        $source = null,
        ?string $confNo = null,
        ?Account $account = null,
        ?Provider $provider = null,
        ?Geotag $geotag = null,
        ?Map $map = null,
        bool $changed = false,
        array $phones = [],
        ?Changes $changes = null,
        ?Useragent $agent = null
    ) {
        parent::__construct($id, $startDate, $endDate, $localDate);
        $this->changed = $changed;
        $this->map = $map;
        $this->confNo = $confNo;
        $this->source = $source;
        $this->account = $account;
        $this->provider = $provider;
        $this->phones = $phones;
        $this->changes = $changes;
        $this->geotag = $geotag;
        $this->agent = $agent;
    }

    public function setChanged(bool $changed): self
    {
        $this->changed = $changed;

        return $this;
    }

    public function isChanged(): bool
    {
        return $this->changed;
    }

    public function setMap(?Map $map): self
    {
        $this->map = $map;

        return $this;
    }

    public function getMap(): ?Map
    {
        return $this->map;
    }

    public function setConfNo(?string $confNo): self
    {
        $this->confNo = $confNo;

        return $this;
    }

    public function getConfNo(): ?string
    {
        return $this->confNo;
    }

    /**
     * @return Itinerary|Tripsegment|null
     */
    public function getSource()
    {
        return $this->source;
    }

    public function getItinerary(): ?Itinerary
    {
        if (is_null($this->source)) {
            return null;
        }

        if ($this->source instanceof Tripsegment) {
            return $this->source->getTripid();
        }

        return $this->source;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function getProvider(): ?Provider
    {
        return $this->provider;
    }

    public function setPhones(array $phones): self
    {
        $this->phones = $phones;

        return $this;
    }

    public function getPhones(): array
    {
        return $this->phones;
    }

    public function setChanges(?Changes $changes): self
    {
        $this->changes = $changes;

        return $this;
    }

    public function getChanges(): ?Changes
    {
        return $this->changes;
    }

    public function setGeotag(?Geotag $geotag): self
    {
        $this->geotag = $geotag;

        return $this;
    }

    public function getGeotag(): ?Geotag
    {
        return $this->geotag;
    }

    public function setAgent(?Useragent $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function getAgent(): ?Useragent
    {
        return $this->agent;
    }

    public function getDiffSourceId()
    {
        return $this->source->getKind() . '.' . $this->source->getId();
    }

    public function getCountry()
    {
        if (!empty($this->geotag)) {
            return $this->geotag->getCountry();
        } else {
            return null;
        }
    }

    public function getTimezoneAbbr(): ?string
    {
        if ($this->geotag) {
            return $this->geotag->getTimeZoneAbbreviation($this->startDate);
        }

        return null;
    }

    public function addFile($file): self
    {
        $this->files[] = $file;

        return $this;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }
}
