<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

class DealRepository extends EntityRepository
{
    public function getRelatedProviders($dealID)
    {
        $connection = $this->getEntityManager()->getConnection();
        $sql = "
		SELECT p.DisplayName 
		FROM DealRelatedProvider d 
		JOIN Provider p USING(ProviderID)
		WHERE DealID = {$dealID}
		";
        $stmt = $connection->executeQuery($sql,
            [$dealID],
            [\PDO::PARAM_INT]
        );
        $providers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];

        foreach ($providers as $provider) {
            $result[] = $provider['DisplayName'];
        }

        return $result;
    }
}
