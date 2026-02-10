<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\AbstractQuery;

class PlanMapQuery implements SegmentMapSourceInterface
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getTimelineMapItems(Usr $user, ?Useragent $agent = null): array
    {
        $conditions = [
            'p.UserID = :userid',
        ];
        $params['userid'] = $user->getUserid();

        if ($agent) {
            $conditions[] = 'p.UserAgentID = :useragentid';
            $params['useragentid'] = $agent->getUseragentid();
        } else {
            $conditions[] = 'p.UserAgentID IS NULL';
        }

        $conditions = implode(' AND ', $conditions);

        $stmt = $this->connection->prepare(
            "
         SELECT
             p.PlanID as id,
             p.StartDate as startDate,
             p.EndDate as endDate,
             0 as deleted,
             CONCAT('P.', p.PlanID) as shareId
         FROM Plan p
         WHERE
             {$conditions}"
        );

        $stmt->execute($params);

        $result = [];

        foreach ($stmt->fetchAll(AbstractQuery::HYDRATE_ARRAY) as $row) {
            $row['startDate'] = new \DateTime($row['startDate']);
            $row['endDate'] = new \DateTime($row['endDate']);

            /** @var SegmentMapItem $planStart */
            $planStart = array_merge($row, [
                'endDate' => $row['startDate'],
                'type' => 'PS',
                'isPlanType' => true,
            ]);

            $result[] = $planStart;

            /** @var SegmentMapItem $planEnd */
            $planEnd = array_merge($row, [
                'startDate' => $row['endDate'],
                'type' => 'PE',
                'isPlanType' => true,
            ]);

            $result[] = $planEnd;
        }

        return $result;
    }
}
