<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class AbPassengerRepository extends EntityRepository
{
    public function getPassengerTemplates(Usr $user)
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $userID = $user->getUserid();
        $stmt = $connection->executeQuery("
				(SELECT
					p.FirstName AS FirstName,
					p.MiddleName AS MiddleName,
					p.LastName AS LastName,
					p.Birthday AS Birthday,
					p.Nationality AS Nationality,
					p.Gender AS Gender,
					r.LastUpdateDate AS Date
				FROM
					AbPassenger p
					JOIN AbRequest r ON r.AbRequestID = p.RequestID
				WHERE
					r.UserID = ?)

				UNION ALL

				(SELECT
					u.FirstName AS FirstName,
					u.MidName AS MiddleName,
					u.LastName AS LastName,
					NULL AS Birthday,
					NULL AS Nationality,
					NULL AS Gender,
					u.CreationDateTime AS Date
				FROM
					Usr u
				WHERE
					u.UserID = ?)

				UNION ALL

				(SELECT
					ua.FirstName AS FirstName,
					ua.MidName AS MiddleName,
					ua.LastName AS LastName,
					NULL AS Birthday,
					NULL AS Nationality,
					NULL AS Gender,
					NULL AS Date
				FROM
					UserAgent ua
				WHERE
					ua.AgentID = ?
					AND ua.ClientID IS NULL)

				ORDER BY FirstName
			",
            [$userID, $userID, $userID]
        );
        $uniqueValues = [];
        $convertSQLToDate = function ($str) {
            if (!empty($str)) {
                $str = date_create($str);

                if (!$str) {
                    $str = null;
                }
            }

            return $str;
        };
        $getHash = function ($fn, $ln) {
            return md5(strtolower(trim($fn)) . " " . strtolower(trim($ln)));
        };
        $aVSb = function ($a, $b) use ($convertSQLToDate) {
            $r = ['a' => 0, 'b' => 0];

            foreach (['a' => $a, 'b' => $b] as $k => $val) {
                if (!empty($val['Birthday'])) {
                    $r[$k]++;
                }

                if (!empty($val['MiddleName'])) {
                    $r[$k]++;
                }

                if (!empty($val['Nationality'])) {
                    $r[$k]++;
                }
            }

            if ($r['a'] != $r['b']) {
                return ($r['a'] > $r['b']) ? $a : $b;
            }
            $ad = $convertSQLToDate($a['Date']);
            $bd = $convertSQLToDate($b['Date']);

            if (!empty($ad) && !empty($bd)) {
                return ($ad > $bd) ? $a : $b;
            } elseif (empty($ad) && empty($bd)) {
                return $a;
            } else {
                return (!empty($ad)) ? $a : $b;
            }
        };

        while ($fields = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (empty($fields['FirstName']) || empty($fields['LastName'])) {
                continue;
            }
            $fields['Birthday'] = $convertSQLToDate($fields['Birthday']);
            $hash = $getHash($fields['FirstName'], $fields['LastName']);

            if (!isset($uniqueValues[$hash])) {
                $uniqueValues[$hash] = $fields;
            } else {
                $uniqueValues[$hash] = $aVSb($fields, $uniqueValues[$hash]);
            }
        }

        $templates = [];

        foreach ($uniqueValues as $person) {
            $k = $person['FirstName'] . " " . $person['LastName'];
            $templates[$k] = [
                'FirstName' => $person['FirstName'],
                'LastName' => $person['LastName'],
            ];

            if (!empty($person['MiddleName'])) {
                $templates[$k]['MiddleName'] = $person['MiddleName'];
            }

            if (!empty($person['Birthday'])) {
                $templates[$k]['Birthday'] = $person['Birthday']->format('Y-m-d');
            }

            if (!empty($person['Nationality'])) {
                $templates[$k]['Nationality'] = $person['Nationality'];
            }

            if (!empty($person['Gender'])) {
                $templates[$k]['Gender'] = $person['Gender'];
            }
        }
        ksort($templates);
        reset($templates);

        return $templates;
    }
}
