<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Plan;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class PlanItemQuery
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return Item\ItemInterface[]
     */
    public function getTimelineItems(Usr $user, ?QueryOptions $queryOptions = null)
    {
        $params = ['user' => $user];
        $conditions = '';
        $orderBy = '';

        if ($queryOptions->hasItems()) {
            if ($ids = SegmentMapUtils::filterIdsByType($queryOptions->getItems(), ['PS', 'PE'])) {
                $conditions .= ' AND p.id IN (:ids)';
                $params['ids'] = $ids;
            } else {
                return [];
            }
        }

        $userAgent = $queryOptions ? $queryOptions->getUserAgent() : null;

        if (!empty($userAgent)) {
            $conditions .= " AND p.userAgent  = :userAgent";
            $params['userAgent'] = $userAgent;
        } else {
            $conditions .= " AND p.userAgent IS NULL";
        }

        $startDate = $queryOptions ? $queryOptions->getStartDate() : null;

        if (null !== $startDate) {
            $conditions .= " AND
			(
			 p.startDate >= :startDate OR
			 (
				 CASE WHEN (p.endDate IS NULL) THEN
					 FALSE
				 ELSE
					 CASE WHEN (p.endDate >= :startDate) THEN
						 TRUE
					 ELSE
						 FALSE
					 END
				 END
			 ) = TRUE
			)";
            $params['startDate'] = $startDate;

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasEndDate()) {
                $orderBy = 'ORDER BY p.startDate';
            }
        }

        $endDate = $queryOptions ? $queryOptions->getEndDate() : null;

        if (null !== $endDate) {
            $conditions .= " AND
			 (
				 p.startDate <= :endDate OR
				 (
					 CASE WHEN (p.endDate IS NULL) THEN
						 FALSE
					 ELSE
						 CASE WHEN (p.endDate <= :endDate) THEN
							 TRUE
						 ELSE
							 FALSE
						 END
					 END
				 ) = TRUE
			 )";
            $params['endDate'] = $endDate;

            if ($queryOptions->hasMaxSegments() && !$queryOptions->hasStartDate()) {
                $orderBy = 'ORDER BY p.startDate desc';
            }
        }

        $q = $this->em->createQuery("
		 SELECT
			 p
		 FROM
			 AwardWallet\MainBundle\Entity\Plan p
		 WHERE
			 p.user = :user
			 $conditions
			 $orderBy
			 ");

        $plans = $q->execute($params);
        $periods = [];

        foreach ($plans as $plan) {
            /** @var $plan Plan */
            $periods[] = [
                'start' => $plan->getStartDate(),
                'end' => $plan->getEndDate(),
                'items' => [
                    new Item\PlanStart($plan),
                    new Item\PlanEnd($plan),
                ],
            ];
        }

        // detect overlaps and remove them
        // if plan wholes overlap, remove it
        // if plan parts overlap, remove plan which starts later
        usort($periods, static function ($a, $b) {
            return $a['start'] <=> $b['start'];
        });

        $result = [];

        foreach ($periods as $current) {
            $isOverlapped = false;

            foreach ($result as $existing) {
                if (
                    ($current['start'] >= $existing['start'] && $current['end'] <= $existing['end'])
                    || ($current['start'] < $existing['end'] && $current['end'] > $existing['start'])
                ) {
                    $isOverlapped = true;

                    break;
                }
            }

            if (!$isOverlapped) {
                $result[] = $current;
            }
        }

        return it($result)
            ->flatMap(static function ($period) {
                return $period['items'];
            })
            ->toArray();
    }
}
