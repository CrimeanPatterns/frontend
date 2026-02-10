<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class CurrencyRepository extends EntityRepository
{
    public $countryCodes = [];

    public function getCountryCodes()
    {
        if (empty($this->countryCodes)) {
            $connection = $this->getEntityManager()->getConnection();
            $stmt = $connection->executeQuery("SELECT CountryID, Code FROM Country WHERE Code IS NOT NULL");

            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->countryCodes[(int) $row['CountryID']] = $row['Code'];
            }
        }

        return $this->countryCodes;
    }

    public function getAllPluralLocalizedList(?TranslatorInterface $translator = null): array
    {
        $list = $this->getEntityManager()->getConnection()->fetchAllAssociative('
            SELECT CurrencyID, Name, Plural
            FROM Currency
            ORDER BY Name ASC
        ');

        if (null === $translator) {
            return $list;
        }

        $localized = [];

        foreach ($list as $item) {
            $id = $item['CurrencyID'];
            $localized[$id] = [
                1 => $translator->trans('plural.' . $id, ['%count%' => 1], 'currency'),
                2 => $translator->trans('plural.' . $id, ['%count%' => 2], 'currency'),
            ];
        }

        return $localized;
    }
}
