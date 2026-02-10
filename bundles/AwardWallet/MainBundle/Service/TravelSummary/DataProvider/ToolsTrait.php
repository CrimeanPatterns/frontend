<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\DataProvider;

use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Globals\ArrayHandler;
use Doctrine\ORM\Query;

trait ToolsTrait
{
    public function getPeriods(Owner $owner): array
    {
        [$userId, $agentId] = self::getUserIds($owner);
        $result = $this->getQueryPeriods($userId, $agentId)->getResult(Query::HYDRATE_ARRAY);

        return ArrayHandler::map($result, 'year', 'count');
    }

    /**
     * Get the IDs of the current user and family member, if available.
     *
     * @param Owner $owner an object containing an instance of the current user and an attached user
     * @return int[]
     */
    private static function getUserIds(Owner $owner): array
    {
        $agentId = null;
        $user = $owner->getUser();
        $userAgent = $owner->getFamilyMember();

        if ($userAgent && $userAgent->getClientid()) {
            $user = $userAgent->getClientid();
        } elseif ($userAgent) {
            $agentId = $userAgent->getId();
        }

        return [$user->getId(), $agentId];
    }
}
