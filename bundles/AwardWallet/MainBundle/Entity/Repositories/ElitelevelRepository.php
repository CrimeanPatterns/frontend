<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

class ElitelevelRepository extends EntityRepository
{
    public function allElitePhones($providerId, $rank)
    {
        $repEliteLevel = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class);
        $phones = $repEliteLevel->findByProviderid($providerId);
        $phonesResult = [];

        foreach ($phones as $i => $elitePhone) {
            if ($elitePhone->getCustomersupportphone() != null && in_array($elitePhone->getRank(), $rank)) {
                $phonesResult[$i]['name'] = $elitePhone->getName();
                $phonesResult[$i]['phone'] = $elitePhone->getCustomersupportphone();
            }
        }

        return $phonesResult;
    }

    public function getEliteLevelFieldsByValue($providerID, $valueText)
    {
        $connection = $this->getEntityManager()->getConnection();

        $eliteLevelFields = $connection->executeQuery("
            SELECT
                el.*, tel.ValueText
            FROM
                EliteLevel el
                JOIN TextEliteLevel tel on el.EliteLevelID = tel.EliteLevelID
            WHERE
                el.ProviderID = ?
                AND tel.ValueText = ?
            ORDER BY el.Rank DESC
        ", [$providerID, $valueText], [\PDO::PARAM_INT, \PDO::PARAM_STR])->fetch(\PDO::FETCH_ASSOC);

        return $eliteLevelFields;
    }

    public function nextEliteLevel($providerID, $valueText)
    {
        $connection = $this->getEntityManager()->getConnection();
        $eliteLevelFields = $this->getEliteLevelFieldsByValue($providerID, $valueText);

        if (!empty($eliteLevelFields)) {
            $eliteLevel = $connection->executeQuery("
                SELECT Name
                FROM EliteLevel
                WHERE ProviderID = ?
                  AND `Rank` > ?
                ORDER BY `Rank`
                LIMIT 1
            ", [$providerID, $eliteLevelFields['Rank']], [\PDO::PARAM_INT, \PDO::PARAM_INT])
                ->fetch(\PDO::FETCH_ASSOC);

            if (!empty($eliteLevel)) {
                return $eliteLevel['Name'];
            }
        } else {
            return false;
        }

        return null;
    }

    /**
     * @param $status - EliteStatus from ProviderProperty Table (Kind = 3)
     * @return array() - fields from EliteLevel table
     */
    public function getEliteLevelFields($providerID, $status = null)
    {
        $connection = $this->getEntityManager()->getConnection();
        $cacheKey = "TextEliteLevel_v2";

        // cache elite levels across all users for 1 minute
        if (isset($this->eliteLevelFieldsCache)) {
            $levels = $this->eliteLevelFieldsCache;
        } else {
            $cache = \Cache::getInstance()->get($cacheKey);

            if ($cache !== false && (time() - $cache['time']) < 60) {
                $levels = $cache['data'];
            } else {
                $sql = "
                    SELECT
                        el.*, tel.ValueText, ael.Name as AllianceName
                    FROM
                        EliteLevel el
                        JOIN TextEliteLevel tel on el.EliteLevelID = tel.EliteLevelID
                        LEFT OUTER JOIN AllianceEliteLevel ael
                        ON el.AllianceEliteLevelID = ael.AllianceEliteLevelID
                    ORDER BY el.Rank DESC
                ";
                $stmt = $connection->executeQuery($sql);
                $levels = [];

                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if (!isset($levels[$row['ProviderID']])) {
                        $levels[$row['ProviderID']] = [];
                    }
                    $levels[$row['ProviderID']][$row['ValueText']] = $row;
                    $levels[$row['ProviderID']][$row['Name']] = $row;
                }
                \Cache::getInstance()->set($cacheKey, ['time' => time(), 'data' => $levels], 60);
            }
            $this->eliteLevelFieldsCache = $levels;
        }

        if (isset($status)) {
            if (isset($levels[$providerID]) && is_array($levels[$providerID])) {
                foreach ($levels[$providerID] as $key => $value) {
                    if (strcasecmp($key, $status) == 0) {
                        return $value;
                    }
                }
            }

            return null;
        } else {
            $result = [];

            if (isset($levels[$providerID]) && is_array($levels[$providerID])) {
                foreach ($levels[$providerID] as $row) {
                    $result[] = array_intersect_key(
                        $row,
                        [
                            'Rank' => null,
                            'ByDefault' => null,
                            'ValueText' => null,
                            'Name' => null,
                            'AllianceName' => null,
                        ]
                    );
                }
            }

            return $result;
        }
    }
}
