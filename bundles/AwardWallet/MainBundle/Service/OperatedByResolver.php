<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Airline;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Tripsegment;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\ORM\EntityManagerInterface;

class OperatedByResolver
{
    private EntityManagerInterface $em;

    private ProviderNameResolver $nameResolver;

    private CacheManager $cacheManager;

    public function __construct(EntityManagerInterface $entityManager, ProviderNameResolver $nameResolver, CacheManager $cacheManager)
    {
        $this->em = $entityManager;
        $this->nameResolver = $nameResolver;
        $this->cacheManager = $cacheManager;
    }

    /**
     * @param Tripsegment $segment
     * @return Provider|null
     */
    public function resolveAirProvider($segment)
    {
        $providerId = null;
        $result = null;
        $iata = $segment->getAirline() instanceof Airline ? $segment->getAirline()->getCode() : null;

        $repo = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

        if (isset($iata)) {
            $providerId = $this->cacheManager->load(new CacheItemReference(preg_replace("#[^\w]+#ims", "_", strtolower('1_iata_' . $iata)), Tags::addTagPrefix([Tags::TAG_PROVIDERS, Tags::TAG_IATA]), function () use ($iata, $repo) {
                $provider = $repo->findOneBy(['IATACode' => $iata, 'kind' => PROVIDER_KIND_AIRLINE]);

                if ($provider instanceof Provider) {
                    return $provider->getProviderid();
                }

                return null;
            }));
        }

        if (!isset($providerId) && $airline = $segment->getAirlinename()) {
            $providerId = $this->cacheManager->load(new CacheItemReference(preg_replace("#[^\w]+#ims", "_", strtolower('1_airline_' . $airline)), Tags::addTagPrefix([Tags::TAG_PROVIDERS, Tags::TAG_AIRLINE]), function () use ($airline, $repo) {
                $provider = $repo->findProviderByContainsText(trim($airline));

                if ($provider instanceof Provider) {
                    return $provider->getProviderid();
                }

                return null;
            }));
        }

        if (isset($providerId)) {
            return $repo->find($providerId);
        }

        return $segment->getTripid()->getProvider();
    }

    public function findAircraftByName($name)
    {
        $qb = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Aircraft::class)
            ->createQueryBuilder('a');

        $aircraft = $qb->add('where', $qb->expr()->orX(
            $qb->expr()->like('a.Name', $qb->expr()->literal('%' . $name . '%'))
        ))
            ->getQuery()->getResult();

        return $aircraft;
    }

    public function getManager()
    {
        return $this->em;
    }
}
