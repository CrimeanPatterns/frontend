<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Model\CacheItemReference;
use AwardWallet\MainBundle\Service\Cache\Tags;
use Doctrine\ORM\EntityManagerInterface;

class ProviderNameResolver
{
    public const PREFIX = 'provider_fuzzy_name';

    private EntityManagerInterface $em;

    private CacheManager $cache;

    public function __construct(EntityManagerInterface $em, CacheManager $cache)
    {
        $this->em = $em;
        $this->cache = $cache;
    }

    /**
     * @param array $filterByCode
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function resolve($fuzzyName, $filterByCode = [])
    {
        if (empty($fuzzyName)) {
            return null;
        }

        return $this->cache->load(new CacheItemReference(
            self::getKey($fuzzyName, $filterByCode),
            self::getTags(),
            function () use ($fuzzyName, $filterByCode) {
                return $this->getProviderByFuzzyName($fuzzyName, $filterByCode);
            }
        ));
    }

    /**
     * @param array $filterByCode
     * @return Provider|null
     */
    public function resolveToProvider($fuzzyName, $filterByCode = [])
    {
        $provider = $this->resolve($fuzzyName, $filterByCode);

        if (!empty($provider)) {
            $providerRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

            return $providerRep->find($provider['ProviderID']);
        }

        return null;
    }

    private function getKey($fuzzyName, $filterByCode = [])
    {
        return implode('_', [
            self::PREFIX,
            preg_replace('/[^a-z0-9]/ims', '_', $fuzzyName),
            md5(serialize($filterByCode)),
        ]);
    }

    private static function getTags()
    {
        return Tags::addTagPrefix([
            Tags::TAG_PROVIDERS,
        ]);
    }

    private function getProviderByFuzzyName($fuzzyName, $filterByCode = [])
    {
        $connection = $this->em->getConnection();
        $providerRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Provider::class);

        if (empty($filterByCode)) {
            $sql = '
                SELECT p.*
                FROM Provider p
                ORDER BY p.Accounts DESC';
            $stmt = $connection->executeQuery($sql);
        } else {
            $sql = '
                SELECT p.*
                FROM Provider p
                WHERE p.Code IN (?)
                ORDER BY p.Accounts DESC';
            $stmt = $connection->executeQuery($sql, [$filterByCode], [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY]);
        }
        $providers = $providerRep->searchProviderByText($fuzzyName, null, $stmt);

        if (!empty($providers)) {
            return reset($providers);
        }

        return null;
    }
}
