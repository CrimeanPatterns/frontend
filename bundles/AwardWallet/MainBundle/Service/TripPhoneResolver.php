<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Entity\Provider;
use Doctrine\ORM\EntityManagerInterface;

class TripPhoneResolver
{
    private EntityManagerInterface $entityManager;

    private ProviderPhoneResolver $providerPhoneResolver;

    public function __construct(EntityManagerInterface $entityManager, ProviderPhoneResolver $providerPhoneResolver)
    {
        $this->entityManager = $entityManager;
        $this->providerPhoneResolver = $providerPhoneResolver;
    }

    public function getTripPhones($kind, $tripId, $provider = null, $country = null, $status = null)
    {
        $result = [];
        $result['providerPhone'] = null;
        $result['elitePhones'] = null;
        $result['itineraryPhones'] = $this->entityManager->getRepository(Itinerary::getItineraryClass($kind))->getPhones($tripId);

        if (isset($provider)) {
            $otherPhones = $this->providerPhoneResolver->getUsefulPhones([[
                'provider' => ($provider instanceof Provider ? $provider->getProviderId() : (int) $provider),
                'status' => $status,
                'country' => $country,
            ]]);
            $temp = [];

            foreach ($otherPhones as $phone) {
                $temp[] = [
                    'name' => $phone['Name'],
                    'phone' => $phone['Phone'],
                    'region' => $phone['RegionCaption'],
                ];
            }
            $result['elitePhones'] = $temp;
        }

        return $result;
    }
}
