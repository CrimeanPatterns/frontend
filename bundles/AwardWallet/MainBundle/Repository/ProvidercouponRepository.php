<?php

namespace AwardWallet\MainBundle\Repository;

use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Repository;
use Doctrine\Persistence\ManagerRegistry;

class ProvidercouponRepository extends Repository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Providercoupon::class);
    }

    public function getVaccineDocument(Usr $user, string $disease): ?int
    {
        $diseasePath = \sprintf('$.%s.disease', Providercoupon::FIELD_KEY_VACCINE_CARD);
        $disease = "%{$disease}%";

        return $this->getEntityManager()->getConnection()->executeQuery("
            select pc.ProviderCouponID 
            from ProviderCoupon pc
            where
                pc.UserID = ? and
                pc.UserAgentID is null and
                pc.TypeID = ? and
                pc.CustomFields is not null and
                pc.CustomFields <> '' and
                lower(json_extract(pc.CustomFields, ?)) like ? 
            order by pc.CreationDate desc
            limit 1
        ",
            [$user->getId(), Providercoupon::TYPE_VACCINE_CARD, $diseasePath, $disease],
            [\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR])
            ->fetchNumeric()[0] ?? null;
    }

    /**
     * Обновляет параметр "isArchived".
     *
     * @param array $ids массив идентификаторов купонов
     * @param int $value значение параметра (0 или 1)
     */
    public function updateIsArchivedValue(array $ids, int $value)
    {
        $queryBuilder = $this->createQueryBuilder('p');

        return $queryBuilder->update()
            ->set('p.isarchived', ':isArchived')
            ->add('where', $queryBuilder->expr()->in('p.providercouponid', $ids))
            ->setParameter('isArchived', $value)
            ->getQuery()
            ->execute();
    }
}
