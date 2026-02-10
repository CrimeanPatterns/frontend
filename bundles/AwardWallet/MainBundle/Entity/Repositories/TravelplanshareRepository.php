<?php

namespace AwardWallet\MainBundle\Entity\Repositories;

use AwardWallet\MainBundle\Entity\Travelplan;
use AwardWallet\MainBundle\Entity\Travelplanshare;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use Doctrine\ORM\EntityRepository;

class TravelplanshareRepository extends EntityRepository
{
    public function isAllowManageSharedTravelPlan(Usr $user, $travelPlanID)
    {
        return $this->isTravelPlanSharedWithBusiness($travelPlanID, $user, SITE_MODE == SITE_MODE_PERSONAL);
    }

    public function isAllowAutologinSharedTravelPlan(Usr $user, $travelPlanID)
    {
        return SITE_MODE == SITE_MODE_BUSINESS && $this->isTravelPlanSharedWithBusiness($travelPlanID, $user, true);
    }

    public function isTravelPlanSharedWithBusiness($travelPlanID, Usr $businessUser, $maxAccessLevelCheck = false)
    {
        $connection = $this->getEntityManager()->getConnection();

        if (is_null($travelPlanID)) {
            return false;
        }
        $sql = "
			SELECT 1 AS Result
			FROM TravelPlanShare tps
				JOIN UserAgent ua ON ua.UserAgentID = tps.UserAgentID
				JOIN Usr u ON u.UserID = ua.AgentID
			WHERE
				TravelPlanID = ?
				AND ua.AgentID = ?
				AND ua.IsApproved = 1
				" . (($maxAccessLevelCheck) ? "AND ua.AccessLevel IN (" . ACCESS_WRITE . ", " . ACCESS_ADMIN . ", " . ACCESS_BOOKING_MANAGER . ", " . ACCESS_BOOKING_VIEW_ONLY . ")" : "") . "
		";
        $row = $connection->executeQuery($sql,
            [$travelPlanID, $businessUser->getUserid()],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        )->fetch(\PDO::FETCH_ASSOC);

        return $row !== false;
    }

    /**
     * share all existing user travel plans to useragent
     * usually called after creating new connection between users.
     */
    public function shareUserPlans(Usr $fromUser, Useragent $toAgent)
    {
        $shares = $this->findBy(['useragentid' => $toAgent->getUseragentid()]);
        $sharedPlans = array_map(function (Travelplanshare $share) { return $share->getTravelplanid()->getTravelplanid(); }, $shares);
        /** @var Travelplan[] $plans */
        $plans = $this->getEntityManager()->getRepository(\AwardWallet\MainBundle\Entity\Travelplan::class)->findBy(['userid' => $fromUser->getUserid()]);

        foreach ($plans as $plan) {
            if (!in_array($plan->getTravelplanid(), $sharedPlans)) {
                $share = new Travelplanshare();
                $share->setTravelplanid($plan);
                $share->setUseragentid($toAgent);
                $this->getEntityManager()->persist($share);
            }
        }
        $this->getEntityManager()->flush();
    }
}
