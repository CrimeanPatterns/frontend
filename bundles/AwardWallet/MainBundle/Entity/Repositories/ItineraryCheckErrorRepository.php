<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use Doctrine\ORM\EntityRepository;

class ItineraryCheckErrorRepository extends EntityRepository
{
    public function lookupRecentErrorsForAccount($accountID)
    {
        if (!$accountID) {
            return [];
        }
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
			select
				ItineraryCheckErrorID,
				date(DetectionDate) as "DetectionDate",
				case
					when ErrorType = 1 then "Account check error"
					when ErrorType = 2 then "No update"
					when ErrorType = 3 then "Wrong update"
					when ErrorType = 4 then "Wrong account"
					when ErrorType = 5 then "No future itineraries"
					when ErrorType = 6 then "0 itineraries should be noItinerariesArr"
					when ErrorType = 7 then "Outdated itineraries"
				end as "Error Type",
				case
				   when Status = "1" then "New"
				   when Status = "2" then "In progress"
				   when Status = "3" then "Resolved"
				   when Status = "4" then "Could not reproduce"
				end as "Operating Status",
				Comment
			from ItineraryCheckError
			where
			AccountID = :AccountID
			order by DetectionDate desc
			limit 10;
		';

        return $conn->executeQuery($sql, [':AccountID' => $accountID])->fetchAll();
    }

    public function lookupRecentOtherAccountsErrorsOfProvider($accountID, $providerID)
    {
        if (!$accountID) {
            $accountID = 0;
        }
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
			select
				ItineraryCheckErrorID,
				date(DetectionDate) as "DetectionDate",
				AccountID,
				case
					when ErrorType = 1 then "Account check error"
					when ErrorType = 2 then "No update"
					when ErrorType = 3 then "Wrong update"
					when ErrorType = 4 then "Wrong account"
					when ErrorType = 5 then "No future itineraries"
					when ErrorType = 6 then "0 itineraries should be noItinerariesArr"
					when ErrorType = 7 then "Outdated itineraries"
				end as "Error Type",
				case
				   when Status = "1" then "New"
				   when Status = "2" then "In progress"
				   when Status = "3" then "Resolved"
				   when Status = "4" then "Could not reproduce"
				end as "Operating Status",
				Comment
			from ItineraryCheckError
			where
			AccountID != :AccountID and
			ProviderID = :ProviderID
			order by DetectionDate desc
			limit 10
		';
        $params = [
            ':AccountID' => $accountID,
            ':ProviderID' => $providerID,
        ];

        return $conn->executeQuery($sql, $params)->fetchAll();
    }

    public function lookupRecentProviderErrors($providerID)
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = '
			select
				ItineraryCheckErrorID,
				date(DetectionDate) as "DetectionDate",
				AccountID,
				case
					when ErrorType = 1 then "Account check error"
					when ErrorType = 2 then "No update"
					when ErrorType = 3 then "Wrong update"
					when ErrorType = 4 then "Wrong account"
					when ErrorType = 5 then "No future itineraries"
					when ErrorType = 6 then "0 itineraries should be noItinerariesArr"
					when ErrorType = 7 then "Outdated itineraries"
				end as "Error Type",
				case
				   when Status = "1" then "New"
				   when Status = "2" then "In progress"
				   when Status = "3" then "Resolved"
				   when Status = "4" then "Could not reproduce"
				end as "Operating Status",
				Comment
			from ItineraryCheckError
			where
			ProviderID = :ProviderID
			order by DetectionDate desc
			limit 10
		';
        $params = [
            ':ProviderID' => $providerID,
        ];

        return $conn->executeQuery($sql, $params)->fetchAll();
    }

    public function checkDuplicatesPerDay(
        int $providerId,
        ?int $accountId,
        \DateTime $date,
        int $errorType,
        ?string $errorMsg = '',
        ?string $confNo = null,
        ?string $partner = null
    ): bool {
        $conn = $this->getEntityManager()->getConnection();
        $cnt = 0;
        $dateStart = $date->format("Y-m-d");
        $dateEnd = date("Y-m-d", strtotime("+1 day", strtotime($dateStart)));

        if (isset($accountId)) {
            // checking if an error of this type was recorded for this account during the day
            $SQLQuery = "
            SELECT 
                   COUNT(*) as cnt 
            FROM ItineraryCheckError 
            WHERE ProviderID = :providerID 
              AND AccountID = :accountId 
              AND DetectionDate >= :dateStart 
              AND DetectionDate < :dateEnd
              AND ErrorType = :errorType 
              AND ErrorMessage = :errorMsg
        ";
            $params = [
                ':providerID' => $providerId,
                ':accountId' => $accountId,
                ':dateStart' => $dateStart,
                ':dateEnd' => $dateEnd,
                ':errorType' => $errorType,
                ':errorMsg' => $errorMsg,
            ];
            $types = [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
            ];
        } elseif (isset($confNo) && isset($partner)) {
            // checking if an error of this type was recorded for this confNo and partner during the day
            $SQLQuery = "
            SELECT
                   COUNT(*) as cnt
            FROM ItineraryCheckError
            WHERE ProviderID = :providerID
              AND ConfirmationNumber = :confNo
              AND DetectionDate >= :dateStart
              AND DetectionDate < :dateEnd
              AND ErrorType = :errorType
              AND ErrorMessage= :errorMsg
        ";
            $params = [
                ':providerID' => $providerId,
                ':confNo' => $confNo,
                ':dateStart' => $dateStart,
                ':dateEnd' => $dateEnd,
                ':errorType' => $errorType,
                ':errorMsg' => $errorMsg,
            ];
            $types = [
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_STR,
            ];
        }

        if (isset($SQLQuery)) {
            $cnt = $conn->executeQuery($SQLQuery, $params, $types)->fetch()['cnt'];
        }

        return $cnt > 0;
    }
}
