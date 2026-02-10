<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providerproperty;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;

class ProviderpropertyRepository extends EntityRepository
{
    /**
     * @return Providerproperty[]
     */
    public function getProviderProperties(Provider $provider): array
    {
        $e = Criteria::expr();
        $properties = [];

        $collection = $this->matching(
            Criteria::create()->where(
                $e->orX(
                    $e->eq('providerid', $provider),
                    $e->isNull('providerid')
                )
            )
        );

        /** @var Providerproperty $property */
        foreach ($collection as $property) {
            $properties[$property->getCode()] = $property;
        }

        return $properties;
    }
}
