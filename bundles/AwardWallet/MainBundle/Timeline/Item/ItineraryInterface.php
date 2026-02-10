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

interface ItineraryInterface
{
    public function getIcon(): string;

    public function setChanged(bool $changed);

    public function isChanged(): bool;

    public function setMap(?Map $map);

    public function getMap(): ?Map;

    public function setConfNo(?string $confNo);

    public function getConfNo(): ?string;

    /**
     * @return Itinerary|Tripsegment|null
     */
    public function getSource();

    public function getItinerary(): ?Itinerary;

    public function getAccount(): ?Account;

    public function getProvider(): ?Provider;

    /**
     * @param PhonesSection[]
     */
    public function setPhones(array $phones);

    /**
     * @return PhonesSection[]
     */
    public function getPhones(): array;

    public function setChanges(?Changes $changes);

    public function getChanges(): ?Changes;

    public function setGeotag(?Geotag $geotag);

    public function getGeotag(): ?Geotag;

    public function setAgent(?Useragent $agent);

    public function getAgent(): ?Useragent;

    public function addFile($file);

    public function getFiles(): ?array;
}
