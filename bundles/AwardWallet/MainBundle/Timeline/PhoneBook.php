<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Entity\Type\Phones;

class PhoneBook
{
    private array $airlinePairsMapByIata;

    private Phones $phones;

    private array $eliteLevels;

    /**
     * @var Itinerary|Tripsegment|null
     */
    private $itinerary;

    public function __construct(array $airlinePairsMapByIata, Phones $phones, array $eliteLevels, $itinerary = null)
    {
        $this->airlinePairsMapByIata = $airlinePairsMapByIata;
        $this->phones = $phones;
        $this->eliteLevels = $eliteLevels;
        $this->itinerary = $itinerary;
    }

    /**
     * @param Itinerary|Tripsegment|null $itinerary
     * @return PhonesSection[]
     */
    public function getPhones($itinerary = null): array
    {
        if (is_null($itinerary)) {
            $itinerary = $this->itinerary;
        }

        if (is_null($itinerary)) {
            throw new \InvalidArgumentException('Itinerary should not be null');
        }

        return (new PhonesSectionsBuilder($itinerary, $this->phones, $this->eliteLevels, $this->airlinePairsMapByIata))
            ->buildSections();
    }

    public function getMostImportantPhone($itinerary = null): ?string
    {
        $phones = $this->getPhones($itinerary);

        if (empty($phones)) {
            return null;
        }

        foreach ($phones as $section) {
            foreach ($section->getLocalPhones() as $localPhone) {
                if (!empty($localPhone['Phone'] ?? null)) {
                    return $localPhone['Phone'];
                }
            }

            foreach ($section->getAddressBookPhones() as $addressBookPhone) {
                if (!empty($addressBookPhone['Phone'] ?? null)) {
                    return $addressBookPhone['Phone'];
                }
            }
        }

        return null;
    }
}
